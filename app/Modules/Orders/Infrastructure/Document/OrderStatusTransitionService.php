<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderParticipant;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Modules\Compensation\Contracts\OrderCompensationSync;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;
use App\Modules\Orders\Application\Document\OrderParticipantFields;
use App\Modules\Orders\Application\Document\OrderWordTemplateRepository;
use App\Modules\Orders\Infrastructure\Document\Effects\OrderEffectCatalog;
use App\Modules\Payroll\Contracts\ClosedPeriodCheck;
use App\Modules\Personnel\Contracts\GuardsPersonnelChanges;
use App\Services\ImportCandidateToPersonnel;
use App\Support\Language\AzerbaijaniDateFormatter;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * The single, guarded entry point for changing a Word-engine order's status.
 *
 * Rather than flipping status_id directly, every move goes through the transition
 * graph below, which (a) refuses illegal jumps and (b) runs the right HR side-effect
 * in the right direction: approving a pending order applies its effect (leave record,
 * transfer, termination, rename, hire); cancelling or reverting an approved order
 * reverses it so the employee record returns to its pre-order state. All of it runs in
 * one transaction, and OrderLog's activity log captures the status change.
 *
 *   pending(10)  → approved(20)  | cancelled(30)
 *   approved(20) → cancelled(30) | pending(10, revert)
 *
 * A hire is reversible only while the new employee has no dependent records
 * ({@see HireOrderRevocation}); otherwise the employment ends by a termination order.
 *   cancelled(30)→ pending(10, reopen)
 *
 * Leaving the approved state (revert or cancel) is guarded: it needs a written reason
 * (kept in the transition's activity entry), and it is refused once the month of the
 * order's effective date is closed for pay ({@see ClosedPeriodCheck}) — by then payroll
 * has been computed from it. Approval assigns the automatic number when the order still
 * holds a provisional one ({@see OrderNumbering}) and, after commit, stores the immutable
 * final PDF ({@see OrderFinalPdfService}).
 *
 * A multi-participant (çoxşəxsli) order runs its effect once per participant, all inside
 * the same transaction: every participant is checked first and the first one who cannot go
 * stops the whole approval, naming that person; each one's effect state is kept on their
 * order_participants row and the reversal undoes them all. One outbox event is published
 * per participant.
 */
class OrderStatusTransitionService
{
    /** Allowed target statuses per current status. */
    private const GRAPH = [
        OrderStatusEnum::PENDING->value => [OrderStatusEnum::APPROVED->value, OrderStatusEnum::CANCELLED->value],
        OrderStatusEnum::APPROVED->value => [OrderStatusEnum::CANCELLED->value, OrderStatusEnum::PENDING->value],
        OrderStatusEnum::CANCELLED->value => [OrderStatusEnum::PENDING->value],
    ];

    /** AppealStatus id a candidate moves to once a hire order is approved ("Qəbul olundu"). */
    private const CANDIDATE_HIRED_STATUS = 70;

    /** Shortest reason accepted for taking an order out of the approved state. */
    public const MIN_REASON_LENGTH = 5;

    public function __construct(
        private readonly OrderWordTemplateRepository $templates,
        private readonly OrderEffectCatalog $effects,
        private readonly ImportCandidateToPersonnel $candidateImport,
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly OrderCompensationSync $compensation,
        private readonly IntegrationOutbox $outbox,
        private readonly OrderPeriodGuard $periods,
        private readonly HireOrderRevocation $hireRevocation,
        private readonly GuardsPersonnelChanges $changes,
        private readonly ClosedPeriodCheck $closedPeriods,
        private readonly OrderNumbering $numbering,
        private readonly OrderDocumentBuilder $documents,
        private readonly OrderFinalPdfService $finalPdf,
    ) {}

    /** Approve a pending order (applies its HR side-effect). */
    public function approve(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::APPROVED);
    }

    /**
     * Cancel an order. Reverses the side-effect if it had been approved — which then needs
     * a reason and an open pay period.
     */
    public function cancel(OrderLog $order, ?string $reason = null): void
    {
        $this->transition($order, OrderStatusEnum::CANCELLED, $reason);
    }

    /** Re-open a cancelled order back to pending. */
    public function reopen(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::PENDING);
    }

    /** Revoke an approved order back to pending (reverses the side-effect); needs a reason. */
    public function revert(OrderLog $order, ?string $reason = null): void
    {
        $this->transition($order, OrderStatusEnum::PENDING, $reason);
    }

    /**
     * The statuses this order may move to right now (for building the UI actions).
     *
     * @return array<int,int>
     */
    public function allowedTargets(OrderLog $order): array
    {
        return self::GRAPH[(int) $order->status_id] ?? [];
    }

    /**
     * @param  string|null  $reason  required (min. MIN_REASON_LENGTH chars) when leaving the approved state
     *
     * @throws DomainException when the move is not allowed
     */
    public function transition(OrderLog $order, OrderStatusEnum $to, ?string $reason = null): void
    {
        if ((string) $order->template_render_mode !== OrderIssueService::RENDER_MODE_DOCX) {
            throw new RuntimeException('Only Word-engine orders support status transitions here.');
        }

        $from = (int) $order->status_id;
        $target = $to->value;

        if ($from === $target) {
            return; // no-op
        }

        if (! in_array($target, self::GRAPH[$from] ?? [], true)) {
            throw new DomainException(__('orders::order_composer.errors.invalid_transition'));
        }

        $reason = trim((string) $reason);

        if ($from === OrderStatusEnum::APPROVED->value) {
            $this->guardLeavingApproved($order, $reason);
        }

        DB::transaction(function () use ($order, $from, $target, $reason) {
            // Approving applies the effect; leaving an approved state reverses it.
            // pending↔cancelled carry no side-effect.
            $effectDirection = 'none';
            if ($target === OrderStatusEnum::APPROVED->value) {
                $this->assignNumber($order);
                $this->applyEffect($order);
                $effectDirection = 'applied';
            } elseif ($from === OrderStatusEnum::APPROVED->value) {
                $this->reverseEffect($order);
                $this->finalPdf->release($order);
                $effectDirection = 'reversed';
            }

            $order->update(['status_id' => $target]);

            $this->recordTransition($order, $from, $target, $effectDirection, $reason);
            $this->publish($order, $effectDirection);
        });

        if ($target === OrderStatusEnum::APPROVED->value) {
            $this->captureFinalPdf($order);
        }
    }

    /**
     * Leaving the approved state undoes an effect payroll may already have used: it needs a
     * stated reason, and the month the order took effect in must still be open.
     *
     * @throws DomainException
     */
    private function guardLeavingApproved(OrderLog $order, string $reason): void
    {
        if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
            throw new DomainException(__('orders::order_composer.errors.reason_required', ['min' => self::MIN_REASON_LENGTH]));
        }

        $date = $this->effectiveDate($order);
        $closedBy = $this->closedPeriods->closedBy($date);

        if ($closedBy !== null) {
            throw new DomainException(__('orders::order_composer.errors.period_closed', [
                'period' => $date->format('m.Y'),
                'reason' => __('orders::order_composer.errors.period_closed_by.'.$closedBy),
            ]));
        }
    }

    /**
     * The date the order takes effect: its start date, else its (single) date, else the
     * day it was given.
     */
    public function effectiveDate(OrderLog $order): CarbonInterface
    {
        $snapshot = (array) $order->template_snapshot;
        $template = $this->templates->find((string) ($snapshot['template_code'] ?? ''));
        $orderFields = (array) ($snapshot['fields'] ?? []);

        // A multi-participant order takes effect on its earliest participant's date.
        $fieldSets = [$orderFields];
        if ($template !== null) {
            foreach ($this->participantsOf($order) as $participant) {
                $fieldSets[] = OrderParticipantFields::effective($template, $orderFields, (array) $participant->fields);
            }
        }

        foreach (['start_date', 'date'] as $role) {
            $earliest = null;
            foreach ($fieldSets as $raw) {
                $fields = $template ? $this->effectFields($template, $raw) : [];
                $date = $this->dates->parse(is_scalar($fields[$role] ?? null) ? (string) $fields[$role] : null);
                if ($date !== null && ($earliest === null || $date->lt($earliest))) {
                    $earliest = $date;
                }
            }

            if ($earliest !== null) {
                return $earliest;
            }
        }

        $given = $order->getRawOriginal('given_date');

        return filled($given) ? Carbon::parse((string) $given) : Carbon::now();
    }

    /**
     * An order approved while it still holds a provisional number takes the next number of
     * its sequence now, inside the approval transaction, and its generated document is
     * re-rendered to carry it. A number it already has (typed, or assigned at an earlier
     * approval) is kept — a number is assigned once.
     */
    private function assignNumber(OrderLog $order): void
    {
        if (! OrderNumbering::isProvisional($order->order_no)) {
            return;
        }

        $snapshot = (array) $order->template_snapshot;
        $templateCode = (string) ($snapshot['template_code'] ?? '');
        $date = $this->dates->parse((string) ($snapshot['order_date_text'] ?? ''))
            ?? (filled($order->getRawOriginal('given_date')) ? Carbon::parse((string) $order->getRawOriginal('given_date')) : Carbon::now());

        $provisional = (string) $order->order_no;
        $number = $this->numbering->assign($templateCode, $date);

        $order->update(['order_no' => $number]);
        // The pivot follows order_no by ON UPDATE CASCADE where foreign keys are enforced;
        // this keeps it right where they are not (and is a no-op where they are).
        DB::table('order_log_personnels')->where('order_no', $provisional)->update(['order_no' => $number]);

        $template = $this->templates->find($templateCode);
        if ($template !== null) {
            $this->documents->renumber($order, $template);
        }
    }

    /**
     * Store the immutable final PDF after the approval committed. Never fails the approval:
     * without LibreOffice (or on a failed conversion) it is logged and backfilled later by
     * orders:render-final-pdfs.
     */
    private function captureFinalPdf(OrderLog $order): void
    {
        if (! OrderFinalPdfService::capturesOnApproval()) {
            return;
        }

        try {
            $this->finalPdf->capture($order);
        } catch (Throwable $e) {
            Log::warning('orders.final_pdf.capture_failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Record the transition for the finance system.
     *
     * Inside the same transaction on purpose: if the effect throws after this
     * point, the event disappears with it. Publishing afterwards — or over HTTP
     * at the moment of approval — would hand the counterpart a fact that never
     * happened, and nothing downstream would ever correct it.
     *
     * Only approvals and reversals are published. pending↔cancelled carries no
     * side-effect, so it changes nothing the payroll side can see.
     */
    private function publish(OrderLog $order, string $effectDirection): void
    {
        if ($effectDirection === 'none') {
            return;
        }

        $snapshot = (array) $order->template_snapshot;
        $template = $this->templates->find((string) ($snapshot['template_code'] ?? ''));

        if (! $template) {
            return;
        }

        $orderFields = (array) ($snapshot['fields'] ?? []);
        $participants = $this->participantsOf($order);

        if ($participants->isEmpty()) {
            $this->outbox->record('orders', (string) $order->order_no, $this->eventPayload(
                $order, $template, $effectDirection, $this->personnel($snapshot), $this->effectFields($template, $orderFields),
            ));

            return;
        }

        // One event per participant, same shape: the counterpart books each person's
        // trip/leave on its own. external_id is per participant; order_external_id ties
        // the events of one order together.
        $people = $this->peopleOf($participants);
        foreach ($participants as $participant) {
            $fields = $this->effectFields($template, OrderParticipantFields::effective($template, $orderFields, (array) $participant->fields));

            $this->outbox->record('orders', (string) $order->order_no, [
                ...$this->eventPayload($order, $template, $effectDirection, $people[(int) $participant->personnel_id] ?? null, $fields),
                'external_id' => $order->id.'-'.$participant->position,
                'order_external_id' => (string) $order->id,
                'participant_index' => (int) $participant->position,
                'participant_count' => $participants->count(),
            ]);
        }
    }

    /**
     * The outbox payload for one employee of an order.
     *
     * @param  array<string,mixed>  $fields  role => value
     * @return array<string,mixed>
     */
    private function eventPayload(OrderLog $order, OrderWordTemplate $template, string $effectDirection, ?Personnel $personnel, array $fields): array
    {
        return [
            'external_id' => (string) $order->id,
            'order_no' => (string) $order->order_no,
            'effect' => (string) $template->effect,
            'label' => (string) $template->label,
            'date' => optional($order->given_date)->format('Y-m-d'),
            // The counterpart correlates people by our internal key, never by
            // staff number: that one is editable and cascades, leaving no trace.
            'employee_external_id' => $personnel ? (string) $personnel->id : null,
            'person_uid' => $personnel?->person_uid,
            'status' => $effectDirection === 'applied' ? 'approved' : 'reversed',
            // A hire can be undone here only while the employee has no records yet, so the
            // counterpart must not offer an undo of its own; a revocation still arrives as 'reversed'.
            'reversible' => ! $template->isHire(),
            'start_date' => $this->dateField($fields, 'start_date'),
            'end_date' => $this->dateField($fields, 'end_date'),
            'days' => isset($fields['days']) ? (int) $fields['days'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function dateField(array $fields, string $role): ?string
    {
        return $this->dates->parse($fields[$role] ?? null)?->format('Y-m-d');
    }

    /**
     * Emit a domain-level audit entry for the transition. OrderLog's generic
     * activity log already captures the raw status_id change, but not the semantic
     * verb (approve/cancel/reopen/revert) nor whether the HR side-effect was applied
     * or reversed — which is exactly what an auditor needs to trace a reversed hire
     * or a cancelled transfer.
     */
    private function recordTransition(OrderLog $order, int $from, int $target, string $effectDirection, string $reason = ''): void
    {
        $verb = $this->transitionVerb($from, $target);

        activity('orders')
            ->performedOn($order)
            ->withProperties(array_filter([
                'order_no' => $order->order_no,
                'order_type_id' => $order->order_type_id,
                'from_status' => $from,
                'to_status' => $target,
                'effect' => $effectDirection,
                'reason' => $reason !== '' ? $reason : null,
            ], fn (mixed $value, string $key): bool => $key !== 'reason' || $value !== null, ARRAY_FILTER_USE_BOTH))
            ->event($verb)
            ->log("order.{$verb}");
    }

    /** Map a (from, to) status pair to its semantic verb. */
    private function transitionVerb(int $from, int $target): string
    {
        return match (true) {
            $target === OrderStatusEnum::APPROVED->value => 'approved',
            $target === OrderStatusEnum::CANCELLED->value => 'cancelled',
            $from === OrderStatusEnum::APPROVED->value && $target === OrderStatusEnum::PENDING->value => 'reverted',
            $from === OrderStatusEnum::CANCELLED->value && $target === OrderStatusEnum::PENDING->value => 'reopened',
            default => 'transitioned',
        };
    }

    private function applyEffect(OrderLog $order): void
    {
        $snapshot = (array) $order->template_snapshot;
        $template = $this->templates->find((string) ($snapshot['template_code'] ?? ''));
        if (! $template) {
            return;
        }

        // Hire converts the selected candidate into an active employee.
        if ($template->isHire()) {
            $this->hire($template, $snapshot, $order);

            return;
        }

        $participants = $this->participantsOf($order);
        if ($participants->isNotEmpty()) {
            $this->applyToParticipants($order, $template, $participants);

            return;
        }

        $effect = $this->effects->for($template->effect);
        $personnel = $this->personnel($snapshot);

        // Defensive: a draft stored before the date rules existed (or whose employee has
        // since gone away on these dates) must not put a broken period on record.
        $blocker = $this->periods->approvalBlocker($template, (array) ($snapshot['fields'] ?? []), $personnel);
        if ($blocker !== null) {
            throw new DomainException(__('orders::order_composer.errors.approval_blocked', ['reason' => $blocker]));
        }

        // Dəyişiklik siyasəti: effekt yalnız reyestrdə ona aid sahə qruplarını yaza bilər
        // (qrup «yalnız əmrlə» olsa belə).
        if ($effect && $personnel) {
            $this->changes->allowForEffect((string) $template->effect, fn () => $effect->apply($order, $this->effectFields($template, (array) ($snapshot['fields'] ?? [])), $personnel));
        }
    }

    private function reverseEffect(OrderLog $order): void
    {
        $snapshot = (array) $order->template_snapshot;
        $template = $this->templates->find((string) ($snapshot['template_code'] ?? ''));
        if (! $template) {
            return;
        }

        // A hire is undone only while the new employee has no records of their own yet;
        // otherwise HireOrderRevocation refuses and the employment must end by a termination order.
        if ($template->isHire()) {
            $this->hireRevocation->revoke($order, $snapshot);

            return;
        }

        $effect = $this->effects->for($template->effect);

        $participants = $this->participantsOf($order);
        if ($participants->isNotEmpty()) {
            if ($effect) {
                $people = $this->peopleOf($participants);
                foreach ($participants->reverse() as $participant) {
                    $personnel = $people[(int) $participant->personnel_id] ?? null;
                    if ($personnel !== null) {
                        $fields = $this->participantEffectFields($template, $snapshot, $participant);
                        $this->withParticipantState($order, $participant, fn () => $effect->reverse($order, $fields, $personnel));
                    }
                }
            }

            return;
        }

        $personnel = $this->personnel($snapshot);
        if ($effect && $personnel) {
            $this->changes->allowForEffect((string) $template->effect, fn () => $effect->reverse($order, $this->effectFields($template, (array) ($snapshot['fields'] ?? [])), $personnel));
        }
    }

    /**
     * Approve a multi-participant order: check every participant first (dates, still
     * employed, not away on those days) — the first one who cannot go refuses the whole
     * approval with their name — then apply the effect for each, all in the caller's
     * transaction, so either everyone's record is written or no one's.
     *
     * @param  Collection<int,OrderParticipant>  $participants
     *
     * @throws DomainException
     */
    private function applyToParticipants(OrderLog $order, OrderWordTemplate $template, Collection $participants): void
    {
        $snapshot = (array) $order->template_snapshot;
        $people = $this->peopleOf($participants);

        foreach ($participants as $participant) {
            $personnel = $people[(int) $participant->personnel_id] ?? null;
            $fields = OrderParticipantFields::effective($template, (array) ($snapshot['fields'] ?? []), (array) $participant->fields);
            $name = $personnel ? (string) $personnel->fullname : '#'.$participant->personnel_id;

            $blocker = $personnel === null
                ? __('orders::order_composer.errors.participant_missing')
                : $this->periods->approvalBlocker($template, $fields, $personnel);

            if ($blocker !== null) {
                throw new DomainException(__('orders::order_composer.errors.approval_blocked', [
                    'reason' => __('orders::order_composer.errors.participant_blocked', ['name' => $name, 'reason' => $blocker]),
                ]));
            }
        }

        $effect = $this->effects->for($template->effect);
        if (! $effect) {
            return;
        }

        foreach ($participants as $participant) {
            $personnel = $people[(int) $participant->personnel_id];
            $fields = $this->participantEffectFields($template, $snapshot, $participant);
            $this->withParticipantState($order, $participant, fn () => $effect->apply($order, $fields, $personnel));
        }
    }

    /**
     * The effect's role inputs for one participant: their effective fields mapped to roles,
     * plus their position so an effect can keep per-person keys apart.
     *
     * @param  array<string,mixed>  $snapshot
     * @return array<string,mixed>
     */
    private function participantEffectFields(OrderWordTemplate $template, array $snapshot, OrderParticipant $participant): array
    {
        $raw = OrderParticipantFields::effective($template, (array) ($snapshot['fields'] ?? []), (array) $participant->fields);

        return $this->effectFields($template, $raw) + [OrderParticipantFields::EFFECT_CONTEXT => (int) $participant->position];
    }

    /**
     * Run an effect for one participant with that participant's effect state in the order
     * snapshot (where every effect keeps it, {@see Effects\RemembersEffectState}), then move
     * the resulting state onto the participant's row and restore the order's own.
     */
    private function withParticipantState(OrderLog $order, OrderParticipant $participant, callable $run): void
    {
        $snapshot = (array) $order->template_snapshot;
        $hadState = array_key_exists('effect_state', $snapshot);
        $orderState = $snapshot['effect_state'] ?? null;

        $snapshot['effect_state'] = (array) ($participant->effect_state ?? []);
        $order->forceFill(['template_snapshot' => $snapshot])->save();

        $run();

        $state = (array) data_get($order->template_snapshot, 'effect_state', []);
        $participant->forceFill(['effect_state' => $state === [] ? null : $state])->save();

        $snapshot = (array) $order->template_snapshot;
        if ($hadState) {
            $snapshot['effect_state'] = $orderState;
        } else {
            unset($snapshot['effect_state']);
        }
        $order->forceFill(['template_snapshot' => $snapshot])->save();
    }

    /**
     * A multi-participant order's people in document order (empty for a single-person order).
     *
     * @return Collection<int,OrderParticipant>
     */
    private function participantsOf(OrderLog $order): Collection
    {
        return $order->participants()->get();
    }

    /**
     * @param  Collection<int,OrderParticipant>  $participants
     * @return array<int,Personnel>
     */
    private function peopleOf(Collection $participants): array
    {
        return Personnel::query()->whereKey($participants->pluck('personnel_id')->all())->get()->keyBy('id')->all();
    }

    /**
     * @param  array<string,mixed>  $snapshot
     */
    private function personnel(array $snapshot): ?Personnel
    {
        $id = $snapshot['personnel_id'] ?? null;

        return $id ? Personnel::find($id) : null;
    }

    /**
     * @param  array<string,mixed>  $snapshot
     */
    private function hire(OrderWordTemplate $template, array $snapshot, OrderLog $order): void
    {
        $candidateId = $snapshot['candidate_id'] ?? null;
        $positionId = $snapshot['hire_position_id'] ?? null;
        if (! $candidateId || ! $positionId) {
            return;
        }

        $joinDate = $this->dates->parse(($this->effectFields($template, (array) ($snapshot['fields'] ?? [])))['start_date'] ?? null);
        $structureId = $snapshot['hire_structure_id'] ?? null;

        // İşə qəbul əmri təyinatın, işə qəbul tarixinin və ilkin əmək haqqının qanuni mənbəyidir.
        $this->changes->allowForEffect('hire', fn (): array => $this->candidateImport->handle([[
            'personnel_id' => (int) $candidateId,
            'structure_id' => $structureId,
            'position_id' => (int) $positionId,
            'join_date' => $joinDate?->toDateString() ?? today()->toDateString(),
            // Lets the Candidates module link the hired candidate back to this order.
            'order_id' => $order->id,
            'order_no' => $order->order_no,
        ]], OrderStatusEnum::APPROVED->value));

        $this->seedHireCompensation((int) $candidateId, $joinDate, $order->order_no);

        // The candidate is now hired: move them off the "Əmrə hazır" (30) list to
        // "Qəbul olundu" (70) so they no longer surface in the hire picker.
        \App\Models\Candidate::query()->whereKey($candidateId)->update([
            'status_id' => self::CANDIDATE_HIRED_STATUS,
        ]);

        // Consume the staff-schedule slot the hire fills (filled +1, vacant recomputed).
        app(\App\Services\Staff\StaffScheduleVacancyService::class)
            ->consumeForHire($structureId ? (int) $structureId : null, (int) $positionId);
    }

    /**
     * Seed a draft compensation for the newly-hired employee using the accepted candidate offer salary.
     */
    private function seedHireCompensation(int $candidateId, mixed $joinDate, ?string $orderNo): void
    {
        $application = DB::table('candidate_applications')
            ->where('candidate_id', $candidateId)
            ->whereNotNull('personnel_id')
            ->orderByDesc('converted_at')
            ->first();

        if (! $application) {
            return;
        }

        $tabelNo = DB::table('personnels')->where('id', $application->personnel_id)->value('tabel_no');

        if (! $tabelNo) {
            return;
        }

        $offer = DB::table('candidate_offers')
            ->where('candidate_application_id', $application->id)
            ->whereNotNull('salary_amount')
            ->orderByDesc('id')
            ->first();

        $this->compensation->createDraftForHire(
            (string) $tabelNo,
            (float) ($offer->salary_amount ?? 0),
            $offer->currency ?? 'AZN',
            $joinDate ? \Illuminate\Support\Carbon::parse($joinDate->format('Y-m-d')) : null,
            $orderNo,
        );
    }

    /**
     * Translate the order's raw field values (keyed by variable token) into the effect's
     * structured inputs (keyed by role) using the template's variable→role mapping.
     *
     * @param  array<string,mixed>  $rawFields  token => value
     * @return array<string,mixed> role => value
     */
    private function effectFields(OrderWordTemplate $template, array $rawFields): array
    {
        $fields = [];
        foreach ($template->variables ?? [] as $variable) {
            $role = $variable['effect_role'] ?? null;
            $token = $variable['token'] ?? null;
            if ($role && $token && array_key_exists($token, $rawFields)) {
                $fields[$role] = $rawFields[$token];
            }
        }

        return $fields;
    }
}
