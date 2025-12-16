<?php

namespace App\Modules\Candidates\Livewire;

use App\Modules\Candidates\Support\Traits\CandidateCrud;
use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Models\Candidate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class EditCandidate extends Component
{
    use CandidateCrud;
    use FillComplexArrayTrait;
    use AuthorizesRequests;

    protected function fillCandidate()
    {
        $this->candidateModelData = Candidate::with(['status', 'structure'])
           ->find($this->candidateModel);

        if (! $this->candidateModelData) abort(404);

        $this->authorize('update', $this->candidateModelData);

        $updatedData = $this->candidateModelData->toArray();

        $this->candidate = $this->mapAttributes(
            attributes: [
                'name', 'surname', 'patronymic', 'height', 'military_service',
                'phone', 'birthdate', 'gender', 'knowledge_test',
                'physical_fitness_exam', 'research_date', 'research_result',
                'examination_date', 'appeal_date', 'application_date',
                'requisition_date', 'initial_documents', 'documents_completeness',
                'attitude_to_military', 'characteristics', 'hhk_date', 'hhk_result',
                'useless_info', 'discrediting_information', 'note', 'presented_by',
            ],
            getFrom: $updatedData
        );

        $this->candidate['structure_id'] = $updatedData['structure_id'] ?? null;
        $this->candidate['status_id'] = $updatedData['status_id'] ?? null;
    }

    public function store()
    {
        $this->validate();

        $this->candidateModelData->update($this->modifyArray($this->candidate, $this->candidateModelData->dateList()));

        $this->dispatch('candidateAdded', __('Candidate was updated successfully!'));
    }
}
