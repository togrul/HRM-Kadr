<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use App\Models\JobRequisition;
use App\Modules\Candidates\Livewire\Concerns\InteractsWithRequisitionForm;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class EditRequisition extends Component
{
    use AuthorizesRequests;
    use InteractsWithRequisitionForm;

    public int $requisitionModel;

    public JobRequisition $requisition;

    public function mount(int $requisitionModel): void
    {
        $this->authorize('update', Candidate::class);

        $this->requisitionModel = $requisitionModel;
        $this->requisition = JobRequisition::query()->findOrFail($this->requisitionModel);
        $this->initializeRequisitionForm($this->requisition);
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

        $this->requisition = $this->storeRequisition($this->requisition);

        $this->dispatch('requisitionSaved', __('candidates::recruitment.messages.requisition_saved'));
        $this->dispatch('ui:modal-close');
    }

    public function render()
    {
        return view('candidates::livewire.candidates.edit-requisition');
    }
}
