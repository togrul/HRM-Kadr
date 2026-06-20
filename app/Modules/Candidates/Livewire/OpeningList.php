<?php

namespace App\Modules\Candidates\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Candidate;
use App\Models\JobOpening;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['openingSaved'])]
class OpeningList extends Component
{
    use AuthorizesRequests;
    use InteractsWithRecruitmentPresentation;
    use SideModalAction;
    use WithPagination;

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $pack = 'all';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Candidate::class);
        $this->pack = $this->normalizeRecruitmentPackFilter($this->pack);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function setPack(string $pack): void
    {
        $this->pack = $this->normalizeRecruitmentPackFilter($pack);
        $this->resetPage();
    }

    protected function openingQuery(): Builder
    {
        $effectivePack = $this->effectiveRecruitmentPack($this->pack);

        return JobOpening::query()
            ->with([
                'requisition:id,title,status',
                'structure:id,name',
                'position:id,name',
                'owner:id,name,email',
            ])
            ->withCount('applications')
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($effectivePack !== 'all', fn (Builder $query) => $query->where('profile_pack', $effectivePack))
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $inner): void {
                    $inner->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('note', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByRaw("
                CASE status
                    WHEN 'open' THEN 1
                    WHEN 'draft' THEN 2
                    WHEN 'closed' THEN 3
                    WHEN 'cancelled' THEN 4
                    ELSE 5
                END
            ")
            ->orderByDesc('id');
    }

    #[Computed]
    public function openingRows(): LengthAwarePaginator
    {
        return $this->openingQuery()->paginate(12);
    }

    #[Computed]
    public function activeApplications(): int
    {
        $effectivePack = $this->effectiveRecruitmentPack($this->pack);

        return (int) \App\Models\CandidateApplication::query()
            ->when($effectivePack !== 'all', fn (Builder $query) => $query->whereHas('opening', fn (Builder $opening) => $opening->where('profile_pack', $effectivePack)))
            ->where('status', 'active')
            ->count();
    }

    #[Computed]
    public function totalOpenings(): int
    {
        $effectivePack = $this->effectiveRecruitmentPack($this->pack);

        return (int) JobOpening::query()
            ->when($effectivePack !== 'all', fn (Builder $query) => $query->where('profile_pack', $effectivePack))
            ->count();
    }

    #[Computed]
    public function totalPublished(): int
    {
        $effectivePack = $this->effectiveRecruitmentPack($this->pack);

        return (int) JobOpening::query()
            ->when($effectivePack !== 'all', fn (Builder $query) => $query->where('profile_pack', $effectivePack))
            ->whereNotNull('published_at')
            ->count();
    }

    public function render()
    {
        return view('candidates::livewire.candidates.opening-list');
    }
}
