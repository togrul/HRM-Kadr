<?php

namespace App\Modules\Candidates\Livewire\Concerns;

use App\Models\JobRequisition;
use App\Modules\Candidates\Support\CandidateWorkflowPackResolver;
use App\Modules\Candidates\Support\Traits\BuildsRecruitmentOptions;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

trait InteractsWithRequisitionForm
{
    use BuildsRecruitmentOptions;
    use InteractsWithRecruitmentPresentation;

    public array $form = [];

    public string $searchStructure = '';

    public string $searchPosition = '';

    public string $searchOwner = '';

    protected function initializeRequisitionForm(?JobRequisition $requisition = null, ?CandidateWorkflowPackResolver $resolver = null): void
    {
        if ($requisition) {
            $this->form = $requisition->only([
                'title',
                'structure_id',
                'position_id',
                'profile_pack',
                'employment_type',
                'hiring_reason',
                'headcount',
                'status',
                'opens_at',
                'closes_at',
                'requested_by',
                'owner_id',
                'note',
            ]);
            $this->form['opens_at'] = optional($requisition->opens_at)->format('Y-m-d');
            $this->form['closes_at'] = optional($requisition->closes_at)->format('Y-m-d');

            return;
        }

        $this->form = [
            'title' => '',
            'structure_id' => null,
            'position_id' => null,
            'profile_pack' => $resolver?->resolve() ?? 'military',
            'employment_type' => 'full_time',
            'hiring_reason' => '',
            'headcount' => 1,
            'status' => 'draft',
            'opens_at' => null,
            'closes_at' => null,
            'requested_by' => auth()->id(),
            'owner_id' => auth()->id(),
            'note' => '',
        ];
    }

    protected function requisitionRules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.structure_id' => ['nullable', 'exists:structures,id'],
            'form.position_id' => ['nullable', 'exists:positions,id'],
            'form.profile_pack' => ['required', Rule::in($this->recruitmentAvailablePacks())],
            'form.employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'internship'])],
            'form.hiring_reason' => ['nullable', 'string', 'max:255'],
            'form.headcount' => ['required', 'integer', 'min:1', 'max:999'],
            'form.status' => ['required', Rule::in(['draft', 'open', 'closed', 'cancelled'])],
            'form.opens_at' => ['nullable', 'date'],
            'form.closes_at' => ['nullable', 'date', 'after_or_equal:form.opens_at'],
            'form.requested_by' => ['nullable', 'exists:users,id'],
            'form.owner_id' => ['nullable', 'exists:users,id'],
            'form.note' => ['nullable', 'string'],
        ];
    }

    protected function requisitionValidationAttributes(): array
    {
        return [
            'form.title' => __('candidates::recruitment.labels.title'),
            'form.structure_id' => __('candidates::recruitment.labels.structure'),
            'form.position_id' => __('candidates::recruitment.labels.position'),
            'form.profile_pack' => __('candidates::recruitment.labels.profile_pack'),
            'form.employment_type' => __('candidates::recruitment.labels.employment_type'),
            'form.hiring_reason' => __('candidates::recruitment.labels.hiring_reason'),
            'form.headcount' => __('candidates::recruitment.labels.headcount'),
            'form.status' => __('candidates::recruitment.labels.status'),
            'form.opens_at' => __('candidates::recruitment.labels.opens_at'),
            'form.closes_at' => __('candidates::recruitment.labels.closes_at'),
            'form.requested_by' => __('candidates::recruitment.labels.requested_by'),
            'form.owner_id' => __('candidates::recruitment.labels.owner'),
            'form.note' => __('candidates::recruitment.labels.note'),
        ];
    }

    protected function storeRequisition(?JobRequisition $requisition = null): JobRequisition
    {
        $status = (string) ($this->form['status'] ?? 'draft');
        $payload = array_merge($this->form, [
            'approved_at' => $status === 'open'
                ? ($requisition?->approved_at ?? now())
                : null,
            'requested_by' => $this->form['requested_by'] ?: auth()->id(),
            'owner_id' => $this->form['owner_id'] ?: auth()->id(),
        ]);

        if ($requisition) {
            $requisition->update($payload);

            return $requisition->fresh();
        }

        return JobRequisition::query()->create($payload);
    }

    #[Computed]
    public function structureOptions(): array
    {
        return $this->recruitmentStructureOptions($this->form['structure_id'], 'searchStructure');
    }

    #[Computed]
    public function positionOptions(): array
    {
        return $this->recruitmentPositionOptions($this->form['position_id'], 'searchPosition');
    }

    #[Computed]
    public function ownerOptions(): array
    {
        return $this->recruitmentOwnerOptions($this->form['owner_id'], 'searchOwner');
    }
}
