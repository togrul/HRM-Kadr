<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Position;
use App\Models\Structure;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Application\Document\OrderIssueOutcome;
use App\Modules\Orders\Application\Document\OrderVacationRules;
use App\Services\Staff\StaffScheduleVacancyService;
use App\Services\Vacation\VacationBalanceService;

/**
 * Issues (or re-saves, when editing) a composed Word order: validates the input, gates
 * vacation balance and hire vacancy, persists the order and stores its filled document.
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
    ) {}

    /**
     * @param  bool  $autoVacancy  create/expand the hire's staff-schedule slot instead of asking
     */
    public function issue(OrderWordTemplate $template, OrderComposition $composition, bool $autoVacancy): OrderIssueOutcome
    {
        if (trim($composition->orderNumber) === '') {
            return OrderIssueOutcome::rejected(['orderNumber' => __('orders::order_composer.errors.number_required')]);
        }

        $subjectErrors = $this->subjects->subjectErrors($template, $composition);
        if ($subjectErrors !== []) {
            return OrderIssueOutcome::rejected($subjectErrors);
        }

        $rejection = $this->missingFields($template, $composition) ?? $this->vacationRejection($template, $composition);
        if ($rejection !== null) {
            return $rejection;
        }

        // Staff-schedule (ştat cədvəli) vacancy gate for new hire orders: there must be
        // a free slot for the chosen structure+position, or the author confirms creating one.
        if ($template->isHire() && ! $composition->isEditing()) {
            if ($autoVacancy) {
                $this->vacancies->ensureOneVacancy((int) $composition->hireStructureId, (int) $composition->hirePositionId);
            } elseif ($this->vacancies->vacancy($composition->hireStructureId, $composition->hirePositionId) <= 0) {
                return OrderIssueOutcome::vacancyMissing(__('orders::order_composer.vacancy.confirm', [
                    'structure' => Structure::find($composition->hireStructureId)->name ?? '—',
                    'position' => Position::find($composition->hirePositionId)->name ?? '—',
                ]));
            }
        }

        return $this->persist($template, $composition);
    }

    /**
     * The selected employee's vacation balance for the order's year plus the days this
     * order requests — null unless it is a day-counted vacation with an employee chosen.
     *
     * @return array{year:int,total:int,used:int,remaining:int,requested:int}|null
     */
    public function vacationBalance(OrderWordTemplate $template, OrderComposition $composition): ?array
    {
        if (! $this->vacationRules->isDayCounted($template)) {
            return null;
        }

        $personnel = $this->subjects->personnel($composition->personnelId);
        if (! $personnel) {
            return null;
        }

        $request = $this->vacationRules->request($template, $composition->fields);

        return [...$this->balances->snapshot($personnel, $request['year']), ...$request];
    }

    /**
     * Every declared manual field must be filled — an order document must not be saved
     * with blank slots. Errors go on the inputs and into a summary toast.
     */
    private function missingFields(OrderWordTemplate $template, OrderComposition $composition): ?OrderIssueOutcome
    {
        $errors = [];
        $missing = [];
        foreach ($template->manualFields() as $field) {
            $value = $composition->fields[$field['key']] ?? null;
            if ($value === null || trim((string) $value) === '') {
                $errors['fields.'.$field['key']] = __('orders::order_composer.errors.field_required');
                $missing[] = $field['label'];
            }
        }

        return $missing === [] ? null : OrderIssueOutcome::rejected($errors, __('orders::order_composer.errors.fields_required', [
            'fields' => implode(', ', $missing),
        ]));
    }

    /**
     * Vacation gate: at least one day, never more than the remaining annual balance.
     */
    private function vacationRejection(OrderWordTemplate $template, OrderComposition $composition): ?OrderIssueOutcome
    {
        $balance = $composition->personnelId ? $this->vacationBalance($template, $composition) : null;
        $violation = $balance ? $this->vacationRules->violation($balance) : null;

        return $violation === null ? null : OrderIssueOutcome::rejected(message: $violation);
    }

    private function persist(OrderWordTemplate $template, OrderComposition $composition): OrderIssueOutcome
    {
        $isHire = $template->isHire();
        // Freeze who signed (permanent chief or active delegate) as-of the order date, and
        // print that same person — historical orders keep naming whoever was acting then.
        $signatory = $this->documents->signatory($composition->orderDate);
        $payload = [
            'template_code' => $composition->presetCode,
            'label' => $template->label,
            'personnel_id' => $isHire ? null : $composition->personnelId,
            'candidate_id' => $isHire ? $composition->candidateId : null,
            'hire_structure_id' => $isHire ? $composition->hireStructureId : null,
            'hire_position_id' => $isHire ? $composition->hirePositionId : null,
            'fields' => $composition->fields,
            'order_number' => trim($composition->orderNumber),
            'order_date' => $composition->orderDate,
            'signatory' => $signatory,
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
