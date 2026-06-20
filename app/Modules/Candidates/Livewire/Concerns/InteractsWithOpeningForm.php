<?php

namespace App\Modules\Candidates\Livewire\Concerns;

use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Modules\Candidates\Support\CandidateWorkflowPackResolver;
use App\Modules\Candidates\Support\Traits\BuildsRecruitmentOptions;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

trait InteractsWithOpeningForm
{
    use BuildsRecruitmentOptions;
    use InteractsWithRecruitmentPresentation;

    public array $form = [];

    public string $searchRequisition = '';

    public string $searchStructure = '';

    public string $searchPosition = '';

    public string $searchOwner = '';

    protected function initializeOpeningForm(?JobOpening $opening = null, ?CandidateWorkflowPackResolver $resolver = null): void
    {
        if ($opening) {
            $this->form = $opening->only([
                'job_requisition_id',
                'title',
                'structure_id',
                'position_id',
                'profile_pack',
                'opening_type',
                'headcount',
                'status',
                'published_at',
                'closes_at',
                'owner_id',
                'note',
            ]);
            $this->form['published_at'] = optional($opening->published_at)->format('Y-m-d');
            $this->form['closes_at'] = optional($opening->closes_at)->format('Y-m-d');

            return;
        }

        $this->form = [
            'job_requisition_id' => null,
            'title' => '',
            'structure_id' => null,
            'position_id' => null,
            'profile_pack' => $resolver?->resolve() ?? 'military',
            'opening_type' => 'standard',
            'headcount' => 1,
            'status' => 'draft',
            'published_at' => null,
            'closes_at' => null,
            'owner_id' => auth()->id(),
            'note' => '',
        ];
    }

    protected function openingRules(): array
    {
        return [
            'form.job_requisition_id' => ['nullable', 'exists:job_requisitions,id'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.structure_id' => ['nullable', 'exists:structures,id'],
            'form.position_id' => ['nullable', 'exists:positions,id'],
            'form.profile_pack' => ['required', Rule::in($this->recruitmentAvailablePacks())],
            'form.opening_type' => ['required', Rule::in(['standard', 'replacement', 'reserve', 'internal'])],
            'form.headcount' => ['required', 'integer', 'min:1', 'max:999'],
            'form.status' => ['required', Rule::in(['draft', 'open', 'closed', 'cancelled'])],
            'form.published_at' => ['nullable', 'date'],
            'form.closes_at' => ['nullable', 'date'],
            'form.owner_id' => ['nullable', 'exists:users,id'],
            'form.note' => ['nullable', 'string'],
        ];
    }

    protected function openingValidationAttributes(): array
    {
        return [
            'form.job_requisition_id' => __('candidates::recruitment.labels.requisition'),
            'form.title' => __('candidates::recruitment.labels.title'),
            'form.structure_id' => __('candidates::recruitment.labels.structure'),
            'form.position_id' => __('candidates::recruitment.labels.position'),
            'form.profile_pack' => __('candidates::recruitment.labels.profile_pack'),
            'form.opening_type' => __('candidates::recruitment.labels.opening_type'),
            'form.headcount' => __('candidates::recruitment.labels.headcount'),
            'form.status' => __('candidates::recruitment.labels.status'),
            'form.published_at' => __('candidates::recruitment.labels.published_at'),
            'form.closes_at' => __('candidates::recruitment.labels.closes_at'),
            'form.owner_id' => __('candidates::recruitment.labels.owner'),
            'form.note' => __('candidates::recruitment.labels.note'),
        ];
    }

    protected function storeOpening(?JobOpening $opening = null): JobOpening
    {
        $status = (string) ($this->form['status'] ?? 'draft');
        $payload = array_merge($this->form, [
            'owner_id' => $this->form['owner_id'] ?: auth()->id(),
            'created_by' => $opening?->created_by ?: auth()->id(),
            'published_at' => $status === 'open'
                ? ($this->form['published_at'] ?: $opening?->published_at ?: now())
                : $this->form['published_at'],
        ]);

        if ($opening) {
            $opening->update($payload);

            return $opening->fresh();
        }

        return JobOpening::query()->create($payload);
    }

    public function updatedFormJobRequisitionId($value): void
    {
        if (! $value) {
            return;
        }

        $requisition = JobRequisition::query()
            ->select('id', 'title', 'structure_id', 'position_id', 'profile_pack', 'headcount', 'owner_id', 'closes_at')
            ->find($value);

        if (! $requisition) {
            return;
        }

        $this->form['title'] = $this->form['title'] !== '' ? $this->form['title'] : $requisition->title;
        $this->form['structure_id'] = $requisition->structure_id;
        $this->form['position_id'] = $requisition->position_id;
        $this->form['profile_pack'] = $requisition->profile_pack;
        $this->form['headcount'] = $requisition->headcount;
        $this->form['owner_id'] = $requisition->owner_id ?: $this->form['owner_id'];
        $this->form['closes_at'] = $this->form['closes_at'] ?: optional($requisition->closes_at)->format('Y-m-d');
    }

    #[Computed]
    public function requisitionOptions(): array
    {
        return $this->recruitmentRequisitionOptions($this->form['job_requisition_id'], 'searchRequisition');
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
