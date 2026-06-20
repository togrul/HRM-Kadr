<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\CandidateApplication;
use App\Models\CandidateDocument;
use App\Modules\Candidates\Application\Services\CandidateApplicationReadService;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageService;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ApplicationArtifactTimelinePanel extends Component
{
    use AuthorizesRequests;
    use InteractsWithRecruitmentPresentation;

    public int $applicationId;

    public CandidateApplication $application;

    public function mount(int $applicationId): void
    {
        $this->applicationId = $applicationId;
        $this->loadApplication();
        $this->authorize('view', $this->application);
    }

    #[On('candidate-application-saved')]
    public function refreshApplication(int $applicationId): void
    {
        if ($applicationId !== $this->applicationId) {
            return;
        }

        $this->loadApplication();
    }

    protected function loadApplication(): void
    {
        $this->application = app(CandidateApplicationReadService::class)->detailForArtifacts($this->applicationId);
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
    public function stageArtifactTimeline(): array
    {
        $assessments = $this->application->assessments->groupBy('stage_key');
        $documents = $this->application->documentChecks->groupBy('stage_key');
        $profiles = $this->application->stageProfiles->keyBy('stage_key');
        $uploadedDocs = $this->application->documents
            ->groupBy(fn (CandidateDocument $document) => $document->stage_key ?: 'general');

        return collect($this->stageDefinitions)
            ->map(function (array $stage) use ($assessments, $documents, $profiles, $uploadedDocs): array {
                $stageKey = $stage['key'];

                return [
                    'key' => $stageKey,
                    'label' => $stage['label'],
                    'assessments' => ($assessments[$stageKey] ?? collect())->values()->all(),
                    'documents' => ($documents[$stageKey] ?? collect())->values()->all(),
                    'profile' => $profiles->get($stageKey),
                    'uploads' => ($uploadedDocs[$stageKey] ?? collect())->values()->all(),
                ];
            })
            ->filter(fn (array $stage) => $stage['assessments'] !== [] || $stage['documents'] !== [] || $stage['profile'] !== null || $stage['uploads'] !== [])
            ->values()
            ->all();
    }

    public function render()
    {
        return view('candidates::livewire.candidates.application-artifact-timeline-panel');
    }
}
