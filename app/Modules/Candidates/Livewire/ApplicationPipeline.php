<?php

namespace App\Modules\Candidates\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageService;
use App\Modules\Candidates\Support\Traits\BuildsRecruitmentOptions;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['applicationSaved'])]
class ApplicationPipeline extends Component
{
    use AuthorizesRequests;
    use BuildsRecruitmentOptions;
    use InteractsWithRecruitmentPresentation;
    use SideModalAction;
    use WithPagination;

    #[Url]
    public string $pack = 'all';

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $stage = 'all';

    #[Url]
    public string $opening = 'all';

    #[Url]
    public string $candidate = 'all';

    #[Url]
    public string $search = '';

    public string $searchOpening = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Candidate::class);
        $this->pack = $this->normalizeRecruitmentPackFilter($this->pack);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setPack(string $pack): void
    {
        $this->pack = $this->normalizeRecruitmentPackFilter($pack);
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function setStage(string $stage): void
    {
        $this->stage = $stage;
        $this->resetPage();
    }

    public function setOpening(string $opening): void
    {
        $this->opening = $opening;
        $this->resetPage();
    }

    public function setCandidate(string $candidate): void
    {
        $this->candidate = $candidate;
        $this->resetPage();
    }

    protected function filteredApplicationQuery(bool $withoutStageFilter = false): Builder
    {
        $effectivePack = $this->effectiveRecruitmentPack($this->pack);

        return CandidateApplication::query()
            ->when($effectivePack !== 'all', fn (Builder $query) => $query->whereHas('opening', fn (Builder $opening) => $opening->where('profile_pack', $effectivePack)))
            ->when(is_numeric($this->opening), fn (Builder $query) => $query->where('job_opening_id', (int) $this->opening))
            ->when(is_numeric($this->candidate), fn (Builder $query) => $query->where('candidate_id', (int) $this->candidate))
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when(! $withoutStageFilter && $this->stage !== 'all', fn (Builder $query) => $query->where('current_stage', $this->stage))
            ->when($this->search !== '', function (Builder $query): void {
                $search = $this->search;
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->whereHas('candidate', function (Builder $candidate) use ($search): void {
                        $candidate->where('surname', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%')
                            ->orWhere('patronymic', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    })->orWhereHas('opening', fn (Builder $opening) => $opening->where('title', 'like', '%'.$search.'%'));
                });
            });
    }

    protected function applicationQuery(bool $withoutStageFilter = false): Builder
    {
        return $this->filteredApplicationQuery($withoutStageFilter)
            ->with([
                'candidate:id,name,surname,patronymic,phone',
                'opening:id,title,profile_pack,position_id,structure_id,job_requisition_id',
                'opening.position:id,name',
                'opening.structure:id,name',
                'opening.requisition:id,title',
                'source:id,name',
                'assignedRecruiter:id,name,email',
            ])
            ->latest('moved_at')
            ->latest('id');
    }

    #[Computed]
    public function applicationRows(): LengthAwarePaginator
    {
        return $this->applicationQuery()->paginate(12);
    }

    #[Computed]
    public function pipelineMetrics(): array
    {
        $metrics = $this->filteredApplicationQuery()
            ->selectRaw("
                COUNT(*) as total_applications,
                COUNT(DISTINCT job_opening_id) as total_openings,
                SUM(CASE WHEN current_stage IN ('hired', 'appointed') THEN 1 ELSE 0 END) as hired_count
            ")
            ->first();

        return [
            'total_applications' => (int) ($metrics?->total_applications ?? 0),
            'total_openings' => (int) ($metrics?->total_openings ?? 0),
            'hired_count' => (int) ($metrics?->hired_count ?? 0),
        ];
    }

    #[Computed]
    public function totalApplications(): int
    {
        return $this->pipelineMetrics['total_applications'];
    }

    #[Computed]
    public function totalOpenings(): int
    {
        return $this->pipelineMetrics['total_openings'];
    }

    #[Computed]
    public function hiredCount(): int
    {
        return $this->pipelineMetrics['hired_count'];
    }

    #[Computed]
    public function stageSummary(): array
    {
        $pack = $this->currentOpening?->profile_pack
            ?? $this->effectiveRecruitmentPack($this->pack);

        return app(CandidateApplicationStageService::class)
            ->stageSummaryForQuery($this->applicationQuery(withoutStageFilter: true), $pack);
    }

    #[Computed]
    public function currentOpening()
    {
        if (! is_numeric($this->opening)) {
            return null;
        }

        return \App\Models\JobOpening::query()
            ->select('id', 'title', 'profile_pack')
            ->find((int) $this->opening);
    }

    #[Computed]
    public function currentCandidate()
    {
        if (! is_numeric($this->candidate)) {
            return null;
        }

        return Candidate::query()
            ->select('id', 'surname', 'name', 'patronymic')
            ->find((int) $this->candidate);
    }

    public function render()
    {
        return view('candidates::livewire.candidates.application-pipeline');
    }
}
