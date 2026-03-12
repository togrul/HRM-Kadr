<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingDeliveryMutations;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsQueries;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class ResultsSummary extends Component
{
    use HandlesTrainingDeliveryMutations;
    use InteractsWithTrainingNeedsAccess;
    use InteractsWithTrainingNeedsQueries;
    use WithRuntimeMemo;

    public function mount(): void
    {
        $this->authorizeTrainingNeedsView();
    }

    public function relayEditFeedbackForm(int $feedbackFormId): void
    {
        $this->dispatch('training-needs:edit-feedback-form', feedbackFormId: $feedbackFormId);
    }

    public function relayDeleteFeedbackForm(int $feedbackFormId): void
    {
        $this->dispatch('training-needs:confirm-delete-feedback-form', feedbackFormId: $feedbackFormId);
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.results-summary');
    }
}
