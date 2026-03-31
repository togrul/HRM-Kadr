<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use App\Models\JobOpening;
use App\Modules\Candidates\Livewire\Concerns\InteractsWithOpeningForm;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class EditOpening extends Component
{
    use AuthorizesRequests;
    use InteractsWithOpeningForm;

    public int $openingModel;

    public JobOpening $opening;

    public function mount(int $openingModel): void
    {
        $this->authorize('update', Candidate::class);

        $this->openingModel = $openingModel;
        $this->opening = JobOpening::query()->findOrFail($this->openingModel);
        $this->initializeOpeningForm($this->opening);
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

        $this->opening = $this->storeOpening($this->opening);

        $this->dispatch('openingSaved', __('candidates::recruitment.messages.opening_saved'));
        $this->dispatch('ui:modal-close');
    }

    public function render()
    {
        return view('candidates::livewire.candidates.edit-opening');
    }
}
