<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\CandidateApplication;
use App\Models\CandidateDocument;
use App\Models\CandidateRejectionReason;
use App\Modules\Candidates\Application\Services\CandidateApplicationReadService;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageArtifactService;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageService;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ApplicationStageActionPanel extends Component
{
    use AuthorizesRequests;
    use InteractsWithRecruitmentPresentation;
    use WithFileUploads;

    public int $applicationId;

    public CandidateApplication $application;

    public array $form = [];

    public array $uploadedDocumentFiles = [];

    public function mount(int $applicationId): void
    {
        $this->applicationId = $applicationId;
        $this->loadApplication();
        $this->authorize('view', $this->application);
        $this->syncActionForm();
    }

    #[On('candidate-application-saved')]
    public function refreshApplication(int $applicationId): void
    {
        if ($applicationId !== $this->applicationId) {
            return;
        }

        $this->loadApplication();
        $this->syncActionForm();
    }

    public function updatedFormToStage(): void
    {
        $this->form['decision'] = '';
        $this->form['score'] = null;
        $this->form['rejection_reason_id'] = null;
        $this->form['final_decision'] = '';
        $this->hydrateStageArtifacts($this->currentActionStage());
    }

    public function setTargetStage(string $stage): void
    {
        $allowed = collect($this->stageOptions())->pluck('id')->all();

        if (! in_array($stage, $allowed, true)) {
            return;
        }

        $this->form['to_stage'] = $stage;
        $this->updatedFormToStage();
    }

    public function applyStageTransition(CandidateApplicationStageService $stageService): void
    {
        $validated = $this->validate()['form'];
        $this->authorizeStageAction($validated['to_stage']);

        $context = [
            'occurred_at' => $validated['occurred_at'] ? now()->parse($validated['occurred_at']) : now(),
            'note' => $validated['note'] ?: null,
            'decision' => $validated['decision'] ?: null,
            'score' => $validated['score'] !== null && $validated['score'] !== '' ? (float) $validated['score'] : null,
            'rejection_reason_id' => $validated['rejection_reason_id'] ?: null,
            'final_decision' => $validated['final_decision'] ?: null,
            'assessment_items' => $validated['assessment_items'] ?? [],
            'document_items' => $validated['document_items'] ?? [],
            'profile_fields' => $validated['profile_fields'] ?? [],
            'actor_id' => auth()->id(),
            'payload' => [
                'pack' => $this->currentPack(),
                'ui_context' => 'application_detail',
            ],
        ];

        $stageService->moveToStage($this->application, $validated['to_stage'], $context);

        $this->loadApplication();
        $this->syncActionForm();
        $this->dispatch('candidate-application-saved', applicationId: $this->application->id);
        $this->dispatch('applicationSaved', __('candidates::recruitment.messages.application_stage_saved'));
    }

    protected function rules(): array
    {
        $rules = [
            'form.to_stage' => ['required', Rule::in(collect($this->stageOptions())->pluck('id')->all())],
            'form.occurred_at' => ['required', 'date'],
            'form.note' => ['nullable', 'string'],
            'form.decision' => ['nullable', 'string', 'max:255'],
            'form.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'form.rejection_reason_id' => ['nullable', 'exists:candidate_rejection_reasons,id'],
            'form.final_decision' => ['nullable', 'string', 'max:255'],
            'form.assessment_items' => ['nullable', 'array'],
            'form.assessment_items.*.status' => ['nullable', Rule::in(app(CandidateApplicationStageArtifactService::class)->assessmentStatuses())],
            'form.assessment_items.*.note' => ['nullable', 'string'],
            'form.document_items' => ['nullable', 'array'],
            'form.document_items.*.is_provided' => ['nullable', 'boolean'],
            'form.document_items.*.note' => ['nullable', 'string'],
            'form.profile_fields' => ['nullable', 'array'],
        ];

        foreach ($this->profileFieldDefinitions as $definition) {
            $key = $definition['key'];
            $rules['form.profile_fields.'.$key] = match ($definition['type']) {
                'number' => ['nullable', 'numeric'],
                'date' => ['nullable', 'date'],
                'select' => ['nullable', Rule::in($definition['options'] ?? [])],
                default => ['nullable', 'string'],
            };
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return [
            'form.to_stage' => __('candidates::recruitment.labels.target_stage'),
            'form.occurred_at' => __('candidates::recruitment.labels.occurred_at'),
            'form.note' => __('candidates::recruitment.labels.note'),
            'form.decision' => __('candidates::recruitment.labels.decision'),
            'form.score' => __('candidates::recruitment.labels.score'),
            'form.rejection_reason_id' => __('candidates::recruitment.labels.rejection_reason'),
            'form.final_decision' => __('candidates::recruitment.labels.final_decision'),
            'form.assessment_items.*.status' => __('candidates::recruitment.labels.assessment_status'),
            'form.assessment_items.*.note' => __('candidates::recruitment.labels.assessment_note'),
            'form.document_items.*.is_provided' => __('candidates::recruitment.labels.document_provided'),
            'form.document_items.*.note' => __('candidates::recruitment.labels.document_note'),
            'form.profile_fields.*' => __('candidates::recruitment.labels.stage_profile_field'),
        ];
    }

    protected function loadApplication(): void
    {
        $this->application = app(CandidateApplicationReadService::class)->detailForStageAction($this->applicationId);
    }

    protected function syncActionForm(): void
    {
        $this->form = [
            'to_stage' => $this->nextSuggestedStage(),
            'occurred_at' => now()->format('Y-m-d'),
            'note' => '',
            'decision' => '',
            'score' => null,
            'rejection_reason_id' => null,
            'final_decision' => '',
            'assessment_items' => [],
            'document_items' => [],
            'profile_fields' => [],
        ];

        $this->hydrateStageArtifacts($this->form['to_stage']);
    }

    #[Computed]
    public function currentPack(): string
    {
        return (string) ($this->application->opening?->profile_pack ?: $this->workflowPackResolver()->resolve());
    }

    #[Computed]
    public function stageDefinitions(): array
    {
        return app(CandidateApplicationStageService::class)->stagesForPack($this->currentPack());
    }

    #[Computed]
    public function stageOptions(): array
    {
        return collect($this->stageDefinitions)
            ->reject(fn (array $stage) => $stage['key'] === $this->application->current_stage)
            ->map(fn (array $stage) => ['id' => $stage['key'], 'label' => $stage['label']])
            ->values()
            ->all();
    }

    #[Computed]
    public function rejectionReasonOptions(): array
    {
        return CandidateRejectionReason::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('profile_pack')->orWhere('profile_pack', $this->currentPack());
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CandidateRejectionReason $reason) => ['id' => $reason->id, 'label' => $reason->name])
            ->all();
    }

    public function nextSuggestedStage(): string
    {
        $keys = collect($this->stageDefinitions)->pluck('key')->values();
        $currentIndex = $keys->search($this->application->current_stage);

        if ($currentIndex === false) {
            return (string) ($keys->first() ?? 'applied');
        }

        return (string) ($keys->get($currentIndex + 1) ?? $this->application->current_stage);
    }

    #[Computed]
    public function nextStageLabel(): string
    {
        return $this->recruitmentStageLabel($this->nextSuggestedStage());
    }

    public function finalStageForPack(): string
    {
        return $this->currentPack() === 'public' ? 'appointed' : 'hired';
    }

    public function currentActionStage(): string
    {
        return (string) ($this->form['to_stage'] ?? $this->nextSuggestedStage());
    }

    public function assessmentStatusOptions(): array
    {
        return collect(app(CandidateApplicationStageArtifactService::class)->assessmentStatuses())
            ->map(fn (string $status): array => [
                'id' => $status,
                'label' => __('candidates::recruitment.assessment_statuses.'.$status),
            ])
            ->all();
    }

    #[Computed]
    public function profileFieldDefinitions(): array
    {
        return app(CandidateApplicationStageArtifactService::class)
            ->profileFieldDefinitionsForStage($this->currentPack(), $this->currentActionStage());
    }

    #[Computed]
    public function currentStageDocumentsByKey(): array
    {
        return $this->application->documents
            ->where('stage_key', $this->currentActionStage())
            ->groupBy('document_key')
            ->map(fn ($group) => $group->values()->all())
            ->all();
    }

    public function needsAssessmentFields(): bool
    {
        $stage = $this->currentActionStage();

        return match ($this->currentPack()) {
            'private' => in_array($stage, ['screening', 'manager_review', 'interview', 'assessment', 'offer'], true),
            'public' => in_array($stage, ['eligibility', 'document_review', 'exam', 'commission_interview', 'ranking', 'reserve'], true),
            default => in_array($stage, ['screening', 'aptitude_test', 'physical_test', 'medical_board', 'security_research', 'commission', 'appointment_ready'], true),
        };
    }

    #[Computed]
    public function assessmentChecklist(): array
    {
        return app(CandidateApplicationStageService::class)
            ->assessmentChecklistForStage($this->currentPack(), $this->currentActionStage());
    }

    #[Computed]
    public function documentChecklist(): array
    {
        return app(CandidateApplicationStageService::class)
            ->documentChecklistForStage($this->currentPack(), $this->currentActionStage());
    }

    public function isRejectStage(): bool
    {
        return ($this->form['to_stage'] ?? null) === 'rejected';
    }

    public function isFinalStage(): bool
    {
        return in_array($this->form['to_stage'] ?? null, ['hired', 'appointed'], true);
    }

    #[Computed]
    public function stageActionPermissions(): array
    {
        $user = auth()->user();

        return [
            'transition' => (bool) $user?->can('transition', $this->application),
            'reject' => (bool) $user?->can('reject', $this->application),
            'appoint' => (bool) $user?->can('appoint', $this->application),
        ];
    }

    #[Computed]
    public function canSaveCurrentStageAction(): bool
    {
        return match (true) {
            $this->isRejectStage() => $this->stageActionPermissions['reject'],
            $this->isFinalStage() => $this->stageActionPermissions['appoint'],
            default => $this->stageActionPermissions['transition'],
        };
    }

    protected function authorizeStageAction(string $toStage): void
    {
        if ($toStage === 'rejected') {
            $this->authorize('reject', $this->application);

            return;
        }

        if (in_array($toStage, ['hired', 'appointed'], true)) {
            $this->authorize('appoint', $this->application);

            return;
        }

        $this->authorize('transition', $this->application);
    }

    protected function hydrateStageArtifacts(?string $stage): void
    {
        $stage = (string) $stage;
        $artifactService = app(CandidateApplicationStageArtifactService::class);
        $hydrated = $artifactService->hydrateStageFormState(
            $this->application,
            $stage,
            $this->assessmentChecklist,
            $this->documentChecklist,
        );

        $this->form['assessment_items'] = $hydrated['assessment_items'];
        $this->form['document_items'] = $hydrated['document_items'];
        $this->form['profile_fields'] = $artifactService->hydrateProfileFieldState(
            $this->application,
            $stage,
            $this->profileFieldDefinitions,
        );
    }

    public function uploadStageDocument(string $documentKey): void
    {
        $this->validate([
            'uploadedDocumentFiles.'.$documentKey => ['required', 'array', 'min:1'],
            'uploadedDocumentFiles.'.$documentKey.'.*' => [
                'required',
                File::types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'])
                    ->max(max(1, (int) config('candidates.documents.max_upload_kb', 10240))),
            ],
        ]);

        $disk = (string) config('candidates.documents.disk', 'local');
        $directory = trim((string) config('candidates.documents.directory', 'candidates'), '/');
        $stage = $this->currentActionStage();
        $candidateId = (int) $this->application->candidate_id;

        foreach (($this->uploadedDocumentFiles[$documentKey] ?? []) as $upload) {
            if (! $upload instanceof TemporaryUploadedFile) {
                continue;
            }

            $path = $upload->store($directory.'/'.$candidateId.'/applications/'.$this->application->id.'/'.$stage, $disk);

            CandidateDocument::query()->create([
                'candidate_id' => $candidateId,
                'candidate_application_id' => $this->application->id,
                'display_name' => $upload->getClientOriginalName(),
                'original_name' => $upload->getClientOriginalName(),
                'file_path' => $path,
                'disk' => $disk,
                'mime_type' => $upload->getMimeType(),
                'extension' => Str::lower((string) ($upload->getClientOriginalExtension() ?: pathinfo($path, PATHINFO_EXTENSION))),
                'size_bytes' => (int) $upload->getSize(),
                'category' => 'other',
                'stage_key' => $stage,
                'document_key' => $documentKey,
                'notes' => data_get($this->form, 'document_items.'.$documentKey.'.note'),
                'uploaded_by' => auth()->id(),
                'sort_order' => 0,
            ]);
        }

        $documentNote = data_get($this->form, 'document_items.'.$documentKey.'.note');

        $this->application->documentChecks()->updateOrCreate(
            [
                'stage_key' => $stage,
                'document_key' => $documentKey,
            ],
            [
                'is_provided' => true,
                'note' => filled($documentNote) ? (string) $documentNote : null,
                'actor_id' => auth()->id(),
                'recorded_at' => now(),
            ]
        );

        data_set($this->form, 'document_items.'.$documentKey.'.is_provided', true);
        unset($this->uploadedDocumentFiles[$documentKey]);

        $this->loadApplication();
        $this->hydrateStageArtifacts($stage);
        $this->dispatch('candidate-application-saved', applicationId: $this->application->id);
    }

    public function render()
    {
        return view('candidates::livewire.candidates.application-stage-action-panel');
    }
}
