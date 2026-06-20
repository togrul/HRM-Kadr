<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use App\Modules\Candidates\Livewire\Concerns\InteractsWithOpeningForm;
use App\Modules\Candidates\Support\CandidateWorkflowPackResolver;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AddOpening extends Component
{
    use AuthorizesRequests;
    use InteractsWithOpeningForm;

    public function mount(CandidateWorkflowPackResolver $resolver): void
    {
        $this->authorize('create', Candidate::class);

        $this->initializeOpeningForm(resolver: $resolver);
    }

    protected function rules(): array
    {
        return $this->openingRules();
    }

    protected function validationAttributes(): array
    {
        return $this->openingValidationAttributes();
    }

    public function store(): void
    {
        $this->validate();

        $this->storeOpening();

        $this->dispatch('openingSaved', __('candidates::recruitment.messages.opening_saved'));
        $this->dispatch('ui:modal-close');
    }

    public function render()
    {
        return view('candidates::livewire.candidates.add-opening');
    }
}
