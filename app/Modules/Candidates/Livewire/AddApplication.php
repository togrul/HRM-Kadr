<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageService;
use App\Modules\Candidates\Support\Traits\BuildsRecruitmentOptions;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AddApplication extends Component
{
    use AuthorizesRequests;
    use BuildsRecruitmentOptions;
    use InteractsWithRecruitmentPresentation;

    public ?int $openingModel = null;

    public ?int $candidateModel = null;

    public array $form = [];

    public string $searchOpening = '';

    public string $searchCandidate = '';

    public string $searchSource = '';

    public string $searchRecruiter = '';

    public function mount(?int $openingModel = null, ?int $candidateModel = null): void
    {
        $this->authorize('create', CandidateApplication::class);

        $this->openingModel = $openingModel;
        $this->candidateModel = $candidateModel;

        $this->form = [
            'job_opening_id' => $openingModel,
            'candidate_id' => $candidateModel,
            'candidate_source_id' => null,
            'assigned_recruiter_id' => auth()->id(),
            'applied_at' => now()->format('Y-m-d'),
            'note' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'form.job_opening_id' => ['required', 'exists:job_openings,id'],
            'form.candidate_id' => [
                'required',
                'exists:candidates,id',
                Rule::unique('candidate_applications', 'candidate_id')
                    ->where(fn ($query) => $query->where('job_opening_id', $this->form['job_opening_id'] ?? 0)),
            ],
            'form.candidate_source_id' => ['nullable', 'exists:candidate_sources,id'],
            'form.assigned_recruiter_id' => ['nullable', 'exists:users,id'],
            'form.applied_at' => ['required', 'date'],
            'form.note' => ['nullable', 'string'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.job_opening_id' => __('candidates::recruitment.labels.opening'),
            'form.candidate_id' => __('candidates::recruitment.labels.candidate'),
            'form.candidate_source_id' => __('candidates::recruitment.labels.source'),
            'form.assigned_recruiter_id' => __('candidates::recruitment.labels.assigned_recruiter'),
            'form.applied_at' => __('candidates::recruitment.labels.applied_at'),
            'form.note' => __('candidates::recruitment.labels.note'),
        ];
    }

    #[Computed]
    public function openingOptions(): array
    {
        return $this->recruitmentOpeningOptions($this->form['job_opening_id'], 'searchOpening');
    }

    #[Computed]
    public function candidateOptions(): array
    {
        return $this->recruitmentCandidateOptions($this->form['candidate_id'], 'searchCandidate');
    }

    #[Computed]
    public function sourceOptions(): array
    {
        return $this->recruitmentSourceOptions($this->form['candidate_source_id'], 'searchSource');
    }

    #[Computed]
    public function recruiterOptions(): array
    {
        return $this->recruitmentOwnerOptions($this->form['assigned_recruiter_id'], 'searchRecruiter');
    }

    #[Computed]
    public function selectedOpening(): ?JobOpening
    {
        $openingId = (int) ($this->form['job_opening_id'] ?? 0);

        if (! $openingId) {
            return null;
        }

        return JobOpening::query()
            ->with(['structure:id,name', 'position:id,name'])
            ->find($openingId);
    }

    public function store(CandidateApplicationStageService $stageService): void
    {
        $validated = $this->validate()['form'];
        $opening = JobOpening::query()->findOrFail((int) $validated['job_opening_id']);
        $candidate = Candidate::query()->findOrFail((int) $validated['candidate_id']);

        $stageService->createInitialApplication($candidate, $opening, [
            'candidate_source_id' => $validated['candidate_source_id'] ?: null,
            'assigned_recruiter_id' => $validated['assigned_recruiter_id'] ?: null,
            'applied_at' => $validated['applied_at'] ? now()->parse($validated['applied_at']) : now(),
            'note' => $validated['note'] ?: null,
            'actor_id' => auth()->id(),
        ]);

        $this->dispatch('applicationSaved', __('candidates::recruitment.messages.application_saved'));
        $this->dispatch('ui:modal-close');
    }

    public function render()
    {
        return view('candidates::livewire.candidates.add-application');
    }
}
