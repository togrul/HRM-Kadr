<?php

namespace App\Modules\Candidates\Livewire;

use App\Models\Candidate;
use App\Models\JobRequisition;
use App\Modules\Candidates\Application\Services\CandidateAtsCompletionService;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RequisitionDetail extends Component
{
    use AuthorizesRequests;
    use InteractsWithRecruitmentPresentation;

    public JobRequisition $requisition;

    public string $approvalNote = '';

    public function mount(JobRequisition $requisition): void
    {
        $this->authorize('viewAny', Candidate::class);

        $this->requisition = $requisition;
        $this->loadRequisition();
    }

    public function submitForApproval(CandidateAtsCompletionService $service): void
    {
        $this->authorize('update', Candidate::class);

        $data = $this->validate([
            'approvalNote' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->submitRequisition($this->requisition, auth()->id(), $data['approvalNote'] ?: null);
        $this->approvalNote = '';
        $this->loadRequisition();
    }

    public function approve(CandidateAtsCompletionService $service): void
    {
        $this->authorize('update', Candidate::class);

        $data = $this->validate([
            'approvalNote' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->approveRequisition($this->requisition, auth()->id(), $data['approvalNote'] ?: null);
        $this->approvalNote = '';
        $this->loadRequisition();
    }

    public function reject(CandidateAtsCompletionService $service): void
    {
        $this->authorize('update', Candidate::class);

        $data = $this->validate([
            'approvalNote' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->rejectRequisition($this->requisition, auth()->id(), $data['approvalNote'] ?: null);
        $this->approvalNote = '';
        $this->loadRequisition();
    }

    private function loadRequisition(): void
    {
        $this->requisition = JobRequisition::query()->with([
            'structure:id,name',
            'position:id,name',
            'requester:id,name,email',
            'owner:id,name,email',
            'approver:id,name,email',
            'rejecter:id,name,email',
            'openings' => fn ($query) => $query
                ->with([
                    'structure:id,name',
                    'position:id,name',
                    'owner:id,name,email',
                ])
                ->withCount('applications')
                ->orderByDesc('id'),
        ])->findOrFail($this->requisition->id);
    }

    #[Computed]
    public function totalApplications(): int
    {
        return (int) $this->requisition->openings->sum(fn ($opening): int => (int) ($opening->getAttributes()['applications_count'] ?? 0));
    }

    public function render()
    {
        return view('candidates::livewire.candidates.requisition-detail');
    }
}
