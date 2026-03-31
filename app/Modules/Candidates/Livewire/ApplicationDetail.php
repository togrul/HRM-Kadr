<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\CandidateApplication;
use App\Modules\Candidates\Application\Services\CandidateApplicationReadService;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class ApplicationDetail extends Component
{
    use AuthorizesRequests;
    use InteractsWithRecruitmentPresentation;

    public CandidateApplication $application;

    public function mount(CandidateApplication $application): void
    {
        $this->application = $application;
        $this->loadApplication();
        $this->authorize('view', $this->application);
    }

    #[On('candidate-application-saved')]
    public function refreshApplication(int $applicationId): void
    {
        if ($applicationId !== $this->application->id) {
            return;
        }

        $this->loadApplication();
    }

    protected function loadApplication(): void
    {
        $this->application = app(CandidateApplicationReadService::class)->detailShell($this->application->id);
    }

    public function currentPack(): string
    {
        return (string) ($this->application->opening?->profile_pack ?: $this->workflowPackResolver()->resolve());
    }

    public function render()
    {
        return view('candidates::livewire.candidates.application-detail');
    }
}
