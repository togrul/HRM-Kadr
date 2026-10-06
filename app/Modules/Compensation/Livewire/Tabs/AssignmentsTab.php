<?php

namespace App\Modules\Compensation\Livewire\Tabs;

use App\Models\CompensationComponent;
use App\Models\EmployeeCompensation;
use App\Modules\Compensation\Application\Services\CompensationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;

/**
 * Current pay of the employee picked in the workspace shell, and the form that assigns new pay.
 */
class AssignmentsTab extends CompensationTab
{
    #[Reactive]
    public ?string $tabelNo = null;

    public array $assignmentForm = [
        'regime_id' => null,
        'pay_grade_id' => null,
        'base_amount' => '',
        'currency' => 'AZN',
        'effective_from' => '',
        'order_no' => '',
        'note' => '',
    ];

    public array $assignmentLines = [];

    protected function viewName(): string
    {
        return 'assignments';
    }

    #[Computed]
    public function componentOptions(): array
    {
        return CompensationComponent::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->get(['id', 'name', 'type'])
            ->map(fn (CompensationComponent $c): array => ['id' => $c->id, 'label' => $c->name])
            ->all();
    }

    #[Computed]
    public function currentAssignment(): ?EmployeeCompensation
    {
        if (! $this->tabelNo) {
            return null;
        }

        return app(CompensationService::class)->currentFor($this->tabelNo);
    }

    public function addAssignmentLine(): void
    {
        $this->assignmentLines[] = ['component_id' => null, 'amount' => '', 'percent' => '', 'note' => ''];
    }

    public function removeAssignmentLine(int $index): void
    {
        unset($this->assignmentLines[$index]);
        $this->assignmentLines = array_values($this->assignmentLines);
    }

    public function saveAssignment(CompensationService $service): void
    {
        $this->guardManage();
        abort_unless($this->tabelNo !== null, 422);

        $validated = $this->validate([
            'assignmentForm.regime_id' => 'required|exists:compensation_regimes,id',
            'assignmentForm.pay_grade_id' => 'nullable|exists:pay_grades,id',
            'assignmentForm.base_amount' => 'required|numeric|min:0',
            'assignmentForm.currency' => 'required|string|size:3',
            'assignmentForm.effective_from' => 'required|date',
            'assignmentForm.order_no' => 'nullable|string|max:64',
            'assignmentForm.note' => 'nullable|string|max:2000',
            'assignmentLines.*.component_id' => 'nullable|exists:compensation_components,id',
            'assignmentLines.*.amount' => 'nullable|numeric',
            'assignmentLines.*.percent' => 'nullable|numeric|min:0|max:100',
        ], attributes: $this->fieldLabels([
            'assignmentForm.regime_id' => 'regime',
            'assignmentForm.pay_grade_id' => 'base_amount',
            'assignmentForm.base_amount' => 'base_amount',
            'assignmentForm.currency' => 'currency',
            'assignmentForm.effective_from' => 'effective_from',
            'assignmentForm.order_no' => 'order_no',
            'assignmentForm.note' => 'note',
        ]));

        $service->assignCompensation(
            $this->tabelNo,
            $validated['assignmentForm'],
            $this->assignmentLines,
        );

        $this->assignmentForm = [
            'regime_id' => null, 'pay_grade_id' => null, 'base_amount' => '',
            'currency' => 'AZN', 'effective_from' => '', 'order_no' => '', 'note' => '',
        ];
        $this->assignmentLines = [];
        unset($this->currentAssignment);

        $this->announce('saved');
    }
}
