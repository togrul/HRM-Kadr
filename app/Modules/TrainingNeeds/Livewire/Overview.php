<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsQueries;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class Overview extends Component
{
    use InteractsWithTrainingNeedsAccess;
    use InteractsWithTrainingNeedsQueries;
    use WithRuntimeMemo;

    public function mount(): void
    {
        $this->authorizeTrainingNeedsView();
    }

    public function render(): View
    {
        return view('training-needs::livewire.training-needs.overview');
    }
}
