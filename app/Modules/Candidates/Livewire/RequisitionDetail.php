<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use App\Models\JobRequisition;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RequisitionDetail extends Component
{
    use AuthorizesRequests;
    use InteractsWithRecruitmentPresentation;

    public JobRequisition $requisition;

    public function mount(JobRequisition $requisition): void
    {
        $this->authorize('viewAny', Candidate::class);

        $this->requisition = $requisition->load([
            'structure:id,name',
            'position:id,name',
            'requester:id,name,email',
            'owner:id,name,email',
            'openings' => fn ($query) => $query
                ->with([
                    'structure:id,name',
                    'position:id,name',
                    'owner:id,name,email',
                ])
                ->withCount('applications')
                ->orderByDesc('id'),
        ]);
    }

    #[Computed]
    public function totalApplications(): int
    {
        return (int) $this->requisition->openings->sum('applications_count');
    }

    public function render()
    {
        return view('candidates::livewire.candidates.requisition-detail');
    }
}
