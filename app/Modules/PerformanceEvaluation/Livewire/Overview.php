<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Modules\PerformanceEvaluation\Livewire\Concerns\HandlesPerformanceReportingMutations;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationAccess;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationQueries;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class Overview extends Component
{
    use HandlesPerformanceReportingMutations;
    use InteractsWithPerformanceEvaluationAccess;
    use InteractsWithPerformanceEvaluationQueries;

    public function mount(): void
    {
        $this->authorizePerformanceEvaluationView();
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.overview');
    }
}
