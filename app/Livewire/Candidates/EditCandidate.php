<?php

namespace App\Livewire\Candidates;

use App\Livewire\Traits\CandidateCrud;
use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Models\Candidate;
use Livewire\Component;

class EditCandidate extends Component
{
    use CandidateCrud;
    use FillComplexArrayTrait;

    public $candidateModelData;

    protected function fillCandidate()
    {
        $this->candidateModelData = Candidate::with(['status', 'structure'])
            ->where('id', $this->candidateModel)
            ->first();

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

        $this->handleRelatedEntity(
            entity: 'structure',
            field: 'structure_id',
            fillTo: 'candidate',
            getFrom: $updatedData,
            titleField: 'name'
        );

        $this->handleRelatedEntity(
            entity: 'status',
            field: 'status_id',
            fillTo: 'candidate',
            getFrom: $updatedData,
            titleField: 'name'
        );
    }

    public function store()
    {
        $this->validate();

        $this->candidateModelData->update($this->modifyArray($this->candidate, $this->candidateModelData->dateList()));

        $this->dispatch('candidateAdded', __('Candidate was updated successfully!'));
    }
}
