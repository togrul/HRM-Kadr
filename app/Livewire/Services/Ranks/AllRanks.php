<?php

namespace App\Livewire\Services\Ranks;

use App\Livewire\Traits\SideModalAction;
use App\Models\Rank;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['rankAdded','rankWasDeleted'])]
class AllRanks extends Component
{
    use WithPagination,SideModalAction,AuthorizesRequests;

    #[Url]
    public $status;

    public function setDeleteRank($rankId)
    {
        $this->dispatch('setDeleteRank',$rankId);
    }

    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function mount()
    {
        $this->status = request()->query('status')
                ? (int)request()->query('status')
                : 1;
    }

    public function render()
    {
        $_ranks = Rank::query()
            ->where('is_active',$this->status)
            ->paginate(15)
            ->withQueryString();

        return view('livewire.services.ranks.all-ranks',compact('_ranks'));
    }
}
