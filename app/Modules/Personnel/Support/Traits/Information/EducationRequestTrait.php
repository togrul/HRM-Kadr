<?php

namespace App\Modules\Personnel\Support\Traits\Information;

use App\Models\PersonnelEducationRequest;

trait EducationRequestTrait {
    public array $education = [];

    public int $selectedRequest;

    protected function getEducationRequestsRules(): array
    {
        return [
            'education.education_place' => 'required|min:2',
            'education.request_date' => 'required|date',
            'education.specialty' => 'required|min:2',
        ];
    }

    public function addEducationRequest(): void
    {
        $this->validate($this->validationRules()['educationRequest']);

        $modelInstance = new PersonnelEducationRequest;
        $educationData = $this->modifyArray($this->education, $modelInstance->dateList());
        $this->personnelModelData->educationRequests()->updateOrCreate(
            ['request_date' => $educationData['request_date']],
            $educationData,
        );

        $this->dispatch('contractAdded', __('Education request was added successfully!'));
        $this->dispatchModalCloseEvent();
        $this->reset('education', 'selectedRequest');
    }

    public function updateEducationRequest(PersonnelEducationRequest $educationRequest): void
    {
        $this->selectedRequest = $educationRequest->id;
        $this->education = $educationRequest->only(['education_place', 'specialty', 'description', 'request_date', 'request_result']);
        if (isset($this->education['request_date'])) {
            $this->education['request_date'] = $this->formatDate($this->education['request_date']);
        }
    }

    public function forceDeleteEducationRequest(PersonnelEducationRequest $requestModel): void
    {
        $requestModel->delete();
        $this->dispatch('contractAdded', __('Education request was deleted successfully!'));
        $this->dispatchModalCloseEvent();
    }
}
