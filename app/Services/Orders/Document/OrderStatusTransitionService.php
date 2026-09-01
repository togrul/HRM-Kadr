<?php

namespace App\Services\Orders\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;
use App\Services\ImportCandidateToPersonnel;
use App\Services\Orders\Document\Effects\OrderEffectCatalog;
use App\Support\Language\AzerbaijaniDateFormatter;
use DomainException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
 *   cancelled(30)→ pending(10, reopen)
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

    public function __construct(
        private readonly OrderWordTemplateRepository $templates,
        private readonly OrderEffectCatalog $effects,
        private readonly ImportCandidateToPersonnel $candidateImport,
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly \App\Modules\Compensation\Application\Services\CompensationService $compensation,
        private readonly IntegrationOutbox $outbox,
    ) {}

    /** Approve a pending order (applies its HR side-effect). */
    public function approve(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::APPROVED);
    }

    /** Cancel an order. Reverses the side-effect if it had been approved. */
    public function cancel(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::CANCELLED);
    }

    /** Re-open a cancelled order back to pending. */
    public function reopen(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::PENDING);
    }

    /** Revoke an approved order back to pending (reverses the side-effect). */
    public function revert(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::PENDING);
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

    public function transition(OrderLog $order, OrderStatusEnum $to): void
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

        DB::transaction(function () use ($order, $from, $target) {
            // Approving applies the effect; leaving an approved state reverses it.
            // pending↔cancelled carry no side-effect.
            $effectDirection = 'none';
            if ($target === OrderStatusEnum::APPROVED->value) {
                $this->applyEffect($order);
                $effectDirection = 'applied';
            } elseif ($from === OrderStatusEnum::APPROVED->value) {
                $this->reverseEffect($order);
                $effectDirection = 'reversed';
            }

            $order->update(['status_id' => $target]);

            $this->recordTransition($order, $from, $target, $effectDirection);
            $this->publish($order, $effectDirection);
        });
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

        $fields = $this->effectFields($template, (array) ($snapshot['fields'] ?? []));
        $personnel = $this->personnel($snapshot);

        $this->outbox->record('orders', (string) $order->order_no, [
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
            // A hire cannot be undone here, so the counterpart must not offer an
            // undo it would be unable to honour.
            'reversible' => ! $template->isHire(),
            'start_date' => $this->dateField($fields, 'start_date'),
            'end_date' => $this->dateField($fields, 'end_date'),
            'days' => isset($fields['days']) ? (int) $fields['days'] : null,
        ]);

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
    private function recordTransition(OrderLog $order, int $from, int $target, string $effectDirection): void
    {
        $verb = $this->transitionVerb($from, $target);

        activity('orders')
            ->performedOn($order)
            ->withProperties([
                'order_no' => $order->order_no,
                'order_type_id' => $order->order_type_id,
                'from_status' => $from,
                'to_status' => $target,
                'effect' => $effectDirection,
            ])
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

        $effect = $this->effects->for($template->effect);
        $personnel = $this->personnel($snapshot);
        if ($effect && $personnel) {
            $effect->apply($order, $this->effectFields($template, (array) ($snapshot['fields'] ?? [])), $personnel);
        }
    }

    private function reverseEffect(OrderLog $order): void
    {
        $snapshot = (array) $order->template_snapshot;
        $template = $this->templates->find((string) ($snapshot['template_code'] ?? ''));
        if (! $template) {
            return;
        }

        // Converting a candidate into an employee cannot be safely undone here.
        if ($template->isHire()) {
            throw new DomainException(__('orders::order_composer.errors.hire_irreversible'));
        }

        $effect = $this->effects->for($template->effect);
        $personnel = $this->personnel($snapshot);
        if ($effect && $personnel) {
            $effect->reverse($order, $this->effectFields($template, (array) ($snapshot['fields'] ?? [])), $personnel);
        }
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

        $this->candidateImport->handle([[
            'personnel_id' => (int) $candidateId,
            'structure_id' => $structureId,
            'position_id' => (int) $positionId,
            'join_date' => $joinDate?->toDateString() ?? today()->toDateString(),
        ]], OrderStatusEnum::APPROVED->value);

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
