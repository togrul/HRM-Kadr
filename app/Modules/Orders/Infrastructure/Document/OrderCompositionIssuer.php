<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Position;
use App\Models\Structure;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Application\Document\OrderIssueOutcome;
use App\Modules\Orders\Application\Document\OrderParticipantFields;
use App\Modules\Orders\Application\Document\OrderVacationRules;
use App\Services\Staff\StaffScheduleVacancyService;
use App\Services\Vacation\VacationBalanceService;
use Carbon\Carbon;

/**
 * Issues (or re-saves, when editing) a composed Word order: validates the input, gates
 * vacation balance and the hire/transfer ştat slot, persists the order and stores its
 * filled document.
 */
class OrderCompositionIssuer
{
    public function __construct(
        private readonly OrderSubjectResolver $subjects,
        private readonly OrderDocumentBuilder $documents,
        private readonly OrderIssueService $issuer,
        private readonly OrderVacationRules $vacationRules,
        private readonly VacationBalanceService $balances,
        private readonly StaffScheduleVacancyService $vacancies,
        private readonly OrderPeriodGuard $periods,
        private readonly OrderNumbering $numbering,
        private readonly OrderTerminationLookup $terminations,
    ) {}

    /**
     * @param  bool  $autoVacancy  create/expand the hire's staff-schedule slot instead of asking
     */
    public function issue(OrderWordTemplate $template, OrderComposition $composition, bool $autoVacancy): OrderIssueOutcome
    {
        // With automatic numbering the number may be left empty: it is assigned at approval.
        $number = trim($composition->orderNumber);
        if ($number === '' && ! $this->numbering->isAutomatic()) {
            return OrderIssueOutcome::rejected(['orderNumber' => __('orders::order_composer.errors.number_required')]);
        }

        if ($number !== '' && $this->numberTaken($number, $composition->editOrderId)) {
            return OrderIssueOutcome::rejected(['orderNumber' => __('orders::order_composer.errors.number_taken')]);
        }

        $subjectErrors = $this->subjects->subjectErrors($template, $composition);
        if ($subjectErrors !== []) {
            return OrderIssueOutcome::rejected($subjectErrors);
        }

        $rejection = $this->missingFields($template, $composition)
            ?? $this->periodRejection($template, $composition)
            ?? $this->vacationRejection($template, $composition)
            ?? $this->compensationRejection($template, $composition);
        if ($rejection !== null) {
            return $rejection;
        }

        // Staff-schedule (ştat cədvəli) gate for new hire and transfer orders: the target
        // structure+position should have a free slot. By default the author is asked to
        // create one (or go ahead); with `staff.hire_guard.block` the order is refused.
        $target = $composition->isEditing() ? null : $this->staffTarget($template, $composition);
        if ($target !== null) {
            [$structureId, $positionId] = $target;
            $check = $this->vacancies->check($structureId, $positionId);

            if ($check->blocks()) {
                return OrderIssueOutcome::rejected([], $check->message());
            }

            if ($check->hasWarning()) {
                if ($autoVacancy) {
                    $this->vacancies->ensureOneVacancy($structureId, $positionId);
                } else {
                    return OrderIssueOutcome::vacancyMissing(__('orders::order_composer.vacancy.confirm', [
                        'structure' => Structure::find($structureId)->name ?? '—',
                        'position' => Position::find($positionId)->name ?? '—',
                    ]));
                }
            }
        }

        return $this->persist($template, $composition);
    }

    /**
     * The selected employee's vacation balance for the order's year plus the days this
     * order requests — null unless it is a day-counted vacation with an employee chosen.
     *
     * The balance is read on the leave's start date: the work years open by then, the
     * first one only after its six months (ƏM m.131.1).
     *
     * @return array{year:int,total:int,used:int,remaining:int,requested:int,on:string,work_year:?string,work_years:list<array<string,mixed>>,next_available_from:?string}|null
     */
    public function vacationBalance(OrderWordTemplate $template, OrderComposition $composition, bool $persist = true): ?array
    {
        if (! $this->vacationRules->isDayCounted($template) || $template->isMultiParticipant()) {
            return null;
        }

        $personnel = $this->subjects->personnel($composition->personnelId);
        if (! $personnel) {
            return null;
        }

        $request = $this->vacationRules->request($template, $composition->fields);

        // Displaying the balance must not write the work years; issuing does (it checks it).
        $balance = $this->balances->balanceOn($personnel, Carbon::parse($request['on']), $persist);

        return [...$balance, ...$request];
    }

    /**
     * Unused-leave compensation gate: the employment contract ended or a termination order
     * is on file (unless the organisation allows pay-outs during employment), and no more
     * days than are unused across all open work years.
     */
    private function compensationRejection(OrderWordTemplate $template, OrderComposition $composition): ?OrderIssueOutcome
    {
        if (! $this->vacationRules->isCompensation($template) || ! $composition->personnelId) {
            return null;
        }

        $personnel = $this->subjects->personnel($composition->personnelId);
        if (! $personnel) {
            return null;
        }

        $allowed = $this->balances->compensationAllowed($personnel) || $this->terminations->hasTerminationOrder($personnel);
        $unused = collect($this->balances->balanceOn($personnel, now())['work_years'])->sum(fn (array $year): int => max(0, (int) $year['remaining']));
        $requested = (int) ($this->vacationRules->effectFieldValue($template, $composition->fields, 'days') ?? 0);
        $violation = $this->vacationRules->compensationViolation($allowed, $requested, (int) $unused);

        return $violation === null ? null : OrderIssueOutcome::rejected(message: $violation);
    }

    /**
     * The structure+position an order puts someone into: the hire's chosen slot, or a
     * transfer's new structure/position (an unchanged half falls back to the employee's
     * current one). Null when the order moves nobody.
     *
     * @return array{0:int,1:int}|null
     */
    private function staffTarget(OrderWordTemplate $template, OrderComposition $composition): ?array
    {
        if ($template->isHire()) {
            return $composition->hireStructureId && $composition->hirePositionId
                ? [(int) $composition->hireStructureId, (int) $composition->hirePositionId]
                : null;
        }

        if ($template->effect !== 'transfer') {
            return null;
        }

        $roles = [];
        foreach ($template->variables ?? [] as $variable) {
            $role = $variable['effect_role'] ?? null;
            $key = $variable['field']['key'] ?? $variable['token'];
            if ($role && $key && ! empty($composition->fields[$key])) {
                $roles[$role] = (int) $composition->fields[$key];
            }
        }

        if (! isset($roles['new_structure']) && ! isset($roles['new_position'])) {
            return null;
        }

        $personnel = $this->subjects->personnel($composition->personnelId);
        $structureId = $roles['new_structure'] ?? (int) ($personnel->structure_id ?? 0);
        $positionId = $roles['new_position'] ?? (int) ($personnel->position_id ?? 0);

        return $structureId > 0 && $positionId > 0 ? [$structureId, $positionId] : null;
    }

    /**
     * Every declared manual field must be filled — an order document must not be saved
     * with blank slots. Errors go on the inputs and into a summary toast.
     */
    private function missingFields(OrderWordTemplate $template, OrderComposition $composition): ?OrderIssueOutcome
    {
        $errors = [];
        $missing = [];
        foreach ($template->orderFields() as $field) {
            if (! $field['required']) {
                continue;
            }

            $value = $composition->fields[$field['key']] ?? null;
            if ($value === null || trim((string) $value) === '') {
                $errors['fields.'.$field['key']] = __('orders::order_composer.errors.field_required');
                $missing[] = $field['label'];
            }
        }

        // A multi-participant order: each person's own fields must be filled for them.
        if ($template->isMultiParticipant()) {
            $names = $this->participantNames($composition);
            foreach ($composition->participantList() as $index => $participant) {
                foreach ($template->participantFields() as $field) {
                    if (! $field['required'] || $field['scope'] !== OrderWordTemplate::SCOPE_PARTICIPANT) {
                        continue;
                    }

                    $value = $participant['fields'][$field['key']] ?? null;
                    if ($value === null || is_array($value) || trim((string) $value) === '') {
                        $errors['participants.'.$index.'.fields.'.$field['key']] = __('orders::order_composer.errors.field_required');
                        $missing[] = $field['label'].' — '.($names[$participant['personnel_id']] ?? '#'.$participant['personnel_id']);
                    }
                }
            }
        }

        return $missing === [] ? null : OrderIssueOutcome::rejected($errors, __('orders::order_composer.errors.fields_required', [
            'fields' => implode(', ', $missing),
        ]));
    }

    /**
     * Period gate: coherent dates (end ≥ start, return after end, day count within the
     * span, sensible work year), an active employee, and no overlap with another live
     * leave, vacation or business trip. Re-checked on approval (OrderStatusTransitionService).
     */
    private function periodRejection(OrderWordTemplate $template, OrderComposition $composition): ?OrderIssueOutcome
    {
        if ($template->isHire()) {
            return null;
        }

        if ($template->isMultiParticipant()) {
            return $this->participantPeriodRejection($template, $composition);
        }

        $personnel = $this->subjects->personnel($composition->personnelId);

        $errors = $this->periods->dateErrors($template, $composition->fields, $personnel);
        if ($errors !== []) {
            return OrderIssueOutcome::rejected($errors, __('orders::order_composer.errors.dates_invalid', [
                'details' => implode(' ', array_unique(array_values($errors))),
            ]));
        }

        $blocker = $this->periods->absenceBlocker($template, $composition->fields, $personnel);

        return $blocker === null ? null : OrderIssueOutcome::rejected(['personnelId' => $blocker], $blocker);
    }

    /**
     * The period gate run for every participant with their own effective dates; the first
     * one who cannot go is named, so the author knows whom to drop or correct.
     */
    private function participantPeriodRejection(OrderWordTemplate $template, OrderComposition $composition): ?OrderIssueOutcome
    {
        $list = $composition->participantList();
        $people = $this->peopleById($list);

        foreach ($list as $index => $participant) {
            $personnel = $people[$participant['personnel_id']] ?? null;
            $name = $personnel ? (string) $personnel->fullname : '#'.$participant['personnel_id'];
            $fields = OrderParticipantFields::effective($template, $composition->fields, $participant['fields']);

            $errors = $this->periods->dateErrors($template, $fields, $personnel);
            if ($errors !== []) {
                $reason = implode(' ', array_unique(array_values($errors)));

                return OrderIssueOutcome::rejected(
                    ['participants.'.$index => $reason],
                    __('orders::order_composer.errors.participant_blocked', ['name' => $name, 'reason' => $reason]),
                );
            }

            $blocker = $this->periods->absenceBlocker($template, $fields, $personnel);
            if ($blocker !== null) {
                $message = __('orders::order_composer.errors.participant_blocked', ['name' => $name, 'reason' => $blocker]);

                return OrderIssueOutcome::rejected(['participants.'.$index => $blocker, 'participants' => $message], $message);
            }
        }

        return null;
    }

    /**
     * @param  list<array{personnel_id:int,fields:array<string,mixed>}>  $list
     * @return array<int,\App\Models\Personnel>
     */
    private function peopleById(array $list): array
    {
        $people = [];
        foreach ($this->subjects->participants(array_column($list, 'personnel_id')) as $person) {
            $people[(int) $person->id] = $person;
        }

        return $people;
    }

    /**
     * @return array<int,string>
     */
    private function participantNames(OrderComposition $composition): array
    {
        return array_map(fn ($person): string => (string) $person->fullname, $this->peopleById($composition->participantList()));
    }

    /**
     * Vacation gate: at least one day, never more than the remaining annual balance.
     */
    private function vacationRejection(OrderWordTemplate $template, OrderComposition $composition): ?OrderIssueOutcome
    {
        if ($template->isMultiParticipant()) {
            return $this->participantVacationRejection($template, $composition);
        }

        $balance = $composition->personnelId ? $this->vacationBalance($template, $composition) : null;
        $violation = $balance ? $this->vacationRules->violation($balance) : null;

        return $violation === null ? null : OrderIssueOutcome::rejected(message: $violation);
    }

    /**
     * The vacation gate per participant, each against their own balance and dates.
     */
    private function participantVacationRejection(OrderWordTemplate $template, OrderComposition $composition): ?OrderIssueOutcome
    {
        if (! $this->vacationRules->isDayCounted($template)) {
            return null;
        }

        $list = $composition->participantList();
        $people = $this->peopleById($list);

        foreach ($list as $index => $participant) {
            $personnel = $people[$participant['personnel_id']] ?? null;
            if ($personnel === null) {
                continue;
            }

            $request = $this->vacationRules->request($template, OrderParticipantFields::effective($template, $composition->fields, $participant['fields']));
            $violation = $this->vacationRules->violation([...$this->balances->snapshot($personnel, $request['year']), ...$request]);

            if ($violation !== null) {
                $message = __('orders::order_composer.errors.participant_blocked', ['name' => (string) $personnel->fullname, 'reason' => $violation]);

                return OrderIssueOutcome::rejected(['participants.'.$index => $violation], $message);
            }
        }

        return null;
    }

    /** Another order (deleted ones included — order_no is unique) already carries this number. */
    private function numberTaken(string $number, ?int $exceptOrderId): bool
    {
        return OrderLog::withTrashed()
            ->where('order_no', $number)
            ->when($exceptOrderId !== null, fn ($query) => $query->whereKeyNot($exceptOrderId))
            ->exists();
    }

    /**
     * The number to store: the one typed, else (automatic numbering) a provisional
     * placeholder — the one the order already holds when it is being edited.
     */
    private function orderNumber(OrderComposition $composition): string
    {
        $number = trim($composition->orderNumber);
        if ($number !== '') {
            return $number;
        }

        $current = $composition->isEditing() ? (string) OrderLog::query()->whereKey($composition->editOrderId)->value('order_no') : '';

        return OrderNumbering::isProvisional($current) ? $current : $this->numbering->provisional();
    }

    private function persist(OrderWordTemplate $template, OrderComposition $composition): OrderIssueOutcome
    {
        $isHire = $template->isHire();
        // Freeze who signed (permanent chief or active delegate) as-of the order date, and
        // print that same person — historical orders keep naming whoever was acting then.
        $signatory = $this->documents->signatory($composition->orderDate);
        $multi = $template->isMultiParticipant();
        $payload = [
            'template_code' => $composition->presetCode,
            'label' => $template->label,
            // A multi-participant order keeps its first participant here, so every reader of
            // the single-person snapshot (lists, lookups, older reports) still finds someone.
            'personnel_id' => $isHire ? null : ($multi ? $composition->leadPersonnelId() : $composition->personnelId),
            'candidate_id' => $isHire ? $composition->candidateId : null,
            'hire_structure_id' => $isHire ? $composition->hireStructureId : null,
            'hire_position_id' => $isHire ? $composition->hirePositionId : null,
            'fields' => $composition->fields,
            'order_number' => $this->orderNumber($composition),
            'order_date' => $composition->orderDate,
            'signatory' => $signatory,
            'participants' => $multi
                ? array_map(fn (array $participant): array => [
                    'personnel_id' => $participant['personnel_id'],
                    'fields' => OrderParticipantFields::clean($template, $participant['fields']),
                ], $composition->participantList())
                : null,
        ];

        $values = $this->documents->values($template, $composition, $signatory);

        if ($composition->isEditing()) {
            $order = OrderLog::findOrFail($composition->editOrderId);
            $this->issuer->updateWord($order, $payload);
            $this->documents->store($order, $template, $values);

            return OrderIssueOutcome::saved(__('orders::order_composer.messages.order_updated'));
        }

        $order = $this->issuer->issueWord($payload);

        return OrderIssueOutcome::saved(
            __('orders::order_composer.messages.order_issued'),
            $this->documents->store($order, $template, $values),
        );
    }
}
