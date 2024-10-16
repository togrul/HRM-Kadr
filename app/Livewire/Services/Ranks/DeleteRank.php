<?php

namespace App\Livewire\Services\Ranks;

use App\Models\Rank;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteRank extends Component
{
    public ?Rank $rank;

    #[On('setDeleteRank')]
    public function setDeleteRank($rankId)
    {
        $this->rank = Rank::findOrFail($rankId);

        $this->dispatch('deleteRankWasSet');
    }

    public function deleteRank()
    {
        // $this->authorize('delete',$this->rank);

        Rank::destroy($this->rank->id);

        $this->rank = null;

        $this->dispatch('rankWasDeleted', __('Rank was deleted!'));
    }

    public function render()
    {
        return view('livewire.services.ranks.delete-rank');
    }
}
