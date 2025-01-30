<?php

namespace App\Livewire\Services\Ranks;

use App\Livewire\Traits\SideModalAction;
use App\Models\Rank;
use App\Models\RankCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['rankAdded', 'rankWasDeleted'])]
class AllRanks extends Component
{
    use AuthorizesRequests,SideModalAction,WithPagination;

    #[Url]
    public $status;

    public function setDeleteRank($rankId)
    {
        $this->dispatch('setDeleteRank', $rankId);
    }

    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }


    public function mount()
    {
        $this->status = request()->query('status')
                ? (int) request()->query('status')
                : 1;
    }

    public function render()
    {
        $_ranks = Rank::with('rankCategory')
            ->where('is_active', $this->status)
            ->paginate(15)
            ->withQueryString();

        return view('livewire.services.ranks.all-ranks', compact('_ranks'));
    }
}
