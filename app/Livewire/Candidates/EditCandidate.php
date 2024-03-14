<?php

namespace App\Livewire\Candidates;

use App\Livewire\Traits\CandidateCrud;
use App\Models\Candidate;
use Livewire\Component;

class EditCandidate extends Component
{
    use CandidateCrud;

    public $candidateModelData;

    protected function fillCandidate()
    {
        $this->candidateModelData = Candidate::with(['status','structure'])
                            ->where('id',$this->candidateModel)
                            ->first();


        $updatedData = $this->candidateModelData->toArray();

        $this->candidate = [
            'name' => $updatedData['name'],
            'surname' => $updatedData['surname'],
            'patronymic' => $updatedData['patronymic'],
            'height' => $updatedData['height'],
            'military_service' => $updatedData['military_service'],
            'phone' => $updatedData['phone'],
            'birthdate' => $updatedData['birthdate'],
            'gender' => $updatedData['gender'],
            'knowledge_test' => $updatedData['knowledge_test'],
            'physical_fitness_exam' => $updatedData['physical_fitness_exam'],
            'research_date' => $updatedData['research_date'],
            'research_result' => $updatedData['research_result'],
            'examination_date' => $updatedData['examination_date'],
            'appeal_date' => $updatedData['appeal_date'],
            'application_date' => $updatedData['application_date'],
            'requisition_date' => $updatedData['requisition_date'],
            'initial_documents' => $updatedData['initial_documents'],
            'documents_completeness' => $updatedData['documents_completeness'],
            'attitude_to_military' => $updatedData['attitude_to_military'],
            'characteristics' => $updatedData['characteristics'],
            'hhk_date' => $updatedData['hhk_date'],
            'hhk_result' => $updatedData['hhk_result'],
            'useless_info' => $updatedData['useless_info'],
            'discrediting_information' => $updatedData['discrediting_information'],
            'note' => $updatedData['note'],
            'presented_by' => $updatedData['presented_by']
        ];

        if(!empty($updatedData['structure_id']))
        {
            $this->candidate['structure_id'] = [
                'id' => $updatedData['structure']['id'],
                'name' => $updatedData['structure']['name'],
            ];
            $this->structureId = $updatedData['structure']['id'];
            $this->structureName = $updatedData['structure']['name'];
        }

        if(!empty($updatedData['status_id']))
        {
            $this->candidate['status_id'] = [
                'id' => $updatedData['status']['id'],
                'name' => $updatedData['status']['name'],
            ];
            $this->statusId = $updatedData['status']['id'];
            $this->statusName = $updatedData['status']['name'];
        }
    }

    public function store()
    {
        $this->validate();

        $this->candidateModelData->update($this->modifyArray($this->candidate,$this->candidateModelData->dateList()));

        $this->dispatch('candidateAdded',__('Candidate was updated successfully!'));
    }
}
