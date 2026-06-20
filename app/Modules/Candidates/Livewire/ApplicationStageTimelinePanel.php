<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\CandidateApplication;
use App\Modules\Candidates\Application\Services\CandidateApplicationReadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class ApplicationStageTimelinePanel extends Component
{
    use AuthorizesRequests;

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
        $this->application = app(CandidateApplicationReadService::class)->detailForTimeline($this->applicationId);
    }

    public function render()
    {
        return view('candidates::livewire.candidates.application-stage-timeline-panel');
    }
}
