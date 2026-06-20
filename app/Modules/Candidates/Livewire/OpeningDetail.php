<?php

namespace App\Modules\Candidates\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Candidate;
use App\Models\JobOpening;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageService;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['applicationSaved'])]
class OpeningDetail extends Component
{
    use AuthorizesRequests;
    use InteractsWithRecruitmentPresentation;
    use SideModalAction;

    public JobOpening $opening;

    public function mount(JobOpening $opening): void
    {
        $this->authorize('viewAny', Candidate::class);

        $this->opening = $opening;
        $this->loadOpening();
    }

    public function refreshApplications(): void
    {
        $this->loadOpening();
    }

    #[Computed]
    public function stageSummary(): array
    {
        return app(CandidateApplicationStageService::class)->stageSummaryForOpening($this->opening);
    }

    protected function loadOpening(): void
    {
        $this->opening->load([
            'requisition:id,title,status,profile_pack',
            'structure:id,name',
            'position:id,name',
            'owner:id,name,email',
            'creator:id,name,email',
            'applications' => fn ($query) => $query
                ->with([
                    'candidate:id,name,surname,patronymic,phone',
                    'source:id,name',
                    'assignedRecruiter:id,name,email',
                ])
                ->latest('moved_at')
                ->limit(10),
        ]);
    }

    public function render()
    {
        return view('candidates::livewire.candidates.opening-detail');
    }
}
