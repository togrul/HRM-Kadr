<?php

namespace App\Modules\Candidates\Livewire;

use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Models\Candidate;
use App\Modules\Candidates\Application\Services\CandidateProfileFieldSchemaService;
use App\Modules\Candidates\Support\Traits\CandidateCrud;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class EditCandidate extends Component
{
    use AuthorizesRequests;
    use CandidateCrud;
    use FillComplexArrayTrait;

    protected function fillCandidate(): void
    {
        $this->candidateModelData = Candidate::with([
            'status',
            'structure',
            'latestApplication',
            'latestApplication.opening',
            'latestApplication.opening.requisition',
            'latestApplication.source:id,name',
            'applications' => fn ($query) => $query
                ->with([
                    'opening:id,title,job_requisition_id',
                    'opening.requisition:id,title',
                ])
                ->latest('id'),
        ])
            ->withCount('applications')
            ->withCount([
                'applications as active_applications_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->find($this->candidateModel);

        if (! $this->candidateModelData) {
            abort(404);
        }

        $this->authorize('update', $this->candidateModelData);

        $updatedData = $this->candidateModelData->toArray();

        $this->candidate = $this->mapAttributes(
            attributes: array_merge([
                'name', 'surname', 'patronymic', 'phone', 'birthdate', 'gender',
            ], app(CandidateProfileFieldSchemaService::class)->allCandidateAttributeKeys()),
            getFrom: $updatedData
        );

        $this->candidate['structure_id'] = $updatedData['structure_id'] ?? null;
        $this->candidate['status_id'] = $updatedData['status_id'] ?? null;
    }

    public function store(): void
    {
        $this->validate();

        $this->candidateModelData->update($this->modifyArray($this->candidate, $this->candidateModelData->dateList()));

        $this->dispatch('candidateAdded', __('candidates::common.messages.candidate_updated'));
    }
}
