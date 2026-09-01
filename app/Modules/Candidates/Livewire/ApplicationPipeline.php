<?php

namespace App\Modules\Candidates\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageService;
use App\Modules\Candidates\Support\Traits\BuildsRecruitmentOptions;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
                'candidate:id,name,surname,patronymic,phone,birthdate',
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

    /**
     * The board reads at most this many applications in one go. A pipeline larger than
     * this shows its newest cards per column while the column counts stay exact.
     *
     * ponytail: single capped read; switch to ROW_NUMBER() OVER (PARTITION BY current_stage)
     * or per-column lazy loading if a real pipeline ever outgrows it.
     */
    private const BOARD_CARD_CAP = 300;

    /**
     * Stage columns for the board: the ordered stages with their exact counts, each
     * carrying the cards the cap could fetch.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function stageBoard(): array
    {
        $cardsByStage = $this->applicationQuery()
            ->limit(self::BOARD_CARD_CAP)
            ->get()
            ->groupBy('current_stage');

        return collect($this->stageSummary())
            ->when($this->stage !== 'all', fn ($stages) => $stages->where('key', $this->stage))
            ->values()
            ->map(function (array $stage) use ($cardsByStage): array {
                $cards = $cardsByStage->get($stage['key'], collect());

                return $stage + [
                    'cards' => $cards,
                    'hidden' => max(0, (int) $stage['count'] - $cards->count()),
                ];
            })
            ->all();
    }

    /**
     * Panel counts, with the applications figure replaced by the filtered total this
     * screen is actually showing.
     *
     * @return array{candidates: int, applications: int, requisitions: int, openings: int, active_candidates: int}
     */
    #[Computed]
    public function panelCounts(): array
    {
        return array_merge($this->recruitmentPanelCounts(), [
            'applications' => $this->totalApplications(),
        ]);
    }

    /**
     * Open vacancies for the panel, with their headcount and how many people applied.
     *
     * @return \Illuminate\Support\Collection<int, JobOpening>
     */
    #[Computed]
    public function openOpenings()
    {
        return JobOpening::query()
            ->select('id', 'title', 'headcount', 'status')
            ->where('status', 'open')
            ->withCount('applications')
            ->orderByDesc('id')
            ->limit(12)
            ->get();
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
