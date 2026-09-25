<?php

namespace App\Modules\Services\Livewire\Ranks;

use App\Livewire\Traits\SideModalAction;
use App\Models\Rank;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['rankAdded', 'rankWasDeleted'])]
class AllRanks extends Component
{
    use AuthorizesRequests,SideModalAction,WithPagination;
    use AuthorizesSettingsAccess;

    #[Url]
    public $status;

    public function setDeleteRank($rankId): void
    {
        $this->dispatch('setDeleteRank', $rankId);
    }

    public function setStatus($newStatus): void
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->status = request()->query('status')
                ? (int) request()->query('status')
                : 1;
    }

    public function render(): View
    {
        $_ranks = Rank::with('rankCategory')
            ->where('is_active', $this->status)
            ->paginate(15)
            ->withQueryString();

        $_ranks = $this->decorateRanks($_ranks);

        return view('services::livewire.services.ranks.all-ranks', compact('_ranks'));
    }

    protected function decorateRanks(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $start = ($paginated->currentPage() - 1) * $paginated->perPage();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (Rank $rank, int $index) use ($start) {
                $rank->row_no = $start + $index + 1;

                return $rank;
            })
        );

        return $paginated;
    }
}
