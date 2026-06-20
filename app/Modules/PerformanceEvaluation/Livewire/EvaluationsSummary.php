<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationAccess;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationQueries;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class EvaluationsSummary extends Component
{
    use InteractsWithPerformanceEvaluationAccess;
    use InteractsWithPerformanceEvaluationQueries;

    public function mount(): void
    {
        $this->authorizePerformanceEvaluationView();
    }

    public function relayEditEvaluationForm(int $formId): void
    {
        $this->dispatch('performance-evaluation:edit-form', formId: $formId);
    }

    public function relayDeleteEvaluationForm(int $formId): void
    {
        $this->dispatch('performance-evaluation:confirm-delete-form', formId: $formId);
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.evaluations-summary');
    }
}
