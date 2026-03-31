<?php

namespace App\Modules\Candidates\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\Candidate;
use App\Models\JobRequisition;
use App\Modules\Candidates\Support\Traits\InteractsWithRecruitmentPresentation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['requisitionSaved'])]
class RequisitionList extends Component
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

    protected function requisitionQuery(): Builder
    {
        $effectivePack = $this->effectiveRecruitmentPack($this->pack);

        return JobRequisition::query()
            ->with([
                'structure:id,name',
                'position:id,name',
                'requester:id,name,email',
                'owner:id,name,email',
            ])
            ->withCount('openings')
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($effectivePack !== 'all', fn (Builder $query) => $query->where('profile_pack', $effectivePack))
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $inner): void {
                    $inner->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('hiring_reason', 'like', '%'.$this->search.'%')
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
    public function requisitionRows(): LengthAwarePaginator
    {
        return $this->requisitionQuery()->paginate(12);
    }

    #[Computed]
    public function draftCount(): int
    {
        $effectivePack = $this->effectiveRecruitmentPack($this->pack);

        return (int) JobRequisition::query()
            ->when($effectivePack !== 'all', fn (Builder $query) => $query->where('profile_pack', $effectivePack))
            ->where('status', 'draft')
            ->count();
    }

    #[Computed]
    public function openCount(): int
    {
        $effectivePack = $this->effectiveRecruitmentPack($this->pack);

        return (int) JobRequisition::query()
            ->when($effectivePack !== 'all', fn (Builder $query) => $query->where('profile_pack', $effectivePack))
            ->where('status', 'open')
            ->count();
    }

    #[Computed]
    public function totalHeadcount(): int
    {
        $effectivePack = $this->effectiveRecruitmentPack($this->pack);

        return (int) JobRequisition::query()
            ->when($effectivePack !== 'all', fn (Builder $query) => $query->where('profile_pack', $effectivePack))
            ->sum('headcount');
    }

    public function render()
    {
        return view('candidates::livewire.candidates.requisition-list');
    }
}
