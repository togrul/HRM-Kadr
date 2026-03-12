<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Models\PerformanceForm;

trait InteractsWithPerformanceEvaluationState
{
    protected function markEvaluatorStatus(PerformanceForm $form, string $evaluatorType): void
    {
        $column = match ($evaluatorType) {
            'self' => 'self_status',
            'manager' => 'manager_status',
            'hr' => 'hr_status',
            default => null,
        };

        if ($column === null) {
            return;
        }

        $form->update([$column => 'submitted']);
    }
}
