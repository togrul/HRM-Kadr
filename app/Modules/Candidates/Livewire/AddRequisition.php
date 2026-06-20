<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use App\Modules\Candidates\Livewire\Concerns\InteractsWithRequisitionForm;
use App\Modules\Candidates\Support\CandidateWorkflowPackResolver;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AddRequisition extends Component
{
    use AuthorizesRequests;
    use InteractsWithRequisitionForm;

    public function mount(CandidateWorkflowPackResolver $resolver): void
    {
        $this->authorize('create', Candidate::class);

        $this->initializeRequisitionForm(resolver: $resolver);
    }

    protected function rules(): array
    {
        return $this->requisitionRules();
    }

    protected function validationAttributes(): array
    {
        return $this->requisitionValidationAttributes();
    }

    public function store(): void
    {
        $this->validate();

        $this->storeRequisition();

        $this->dispatch('requisitionSaved', __('candidates::recruitment.messages.requisition_saved'));
        $this->dispatch('ui:modal-close');
    }

    public function render()
    {
        return view('candidates::livewire.candidates.add-requisition');
    }
}
