<?php

namespace App\Modules\Services\Livewire\Ranks;

use App\Models\Rank;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteRank extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $rankId = null;

    #[On('setDeleteRank')]
    public function setDeleteRank($rankId)
    {
        $rank = Rank::query()
            ->select('id')
            ->find($rankId);

        if (! $rank) {
            $this->rankId = null;

            return;
        }

        // $this->authorize('delete', $rank);

        $this->rankId = (int) $rank->id;

        $this->dispatch('deleteRankWasSet');
    }

    public function deleteRank()
    {
        if (! $this->rankId) {
            return;
        }

        $rank = Rank::query()
            ->select('id')
            ->find($this->rankId);

        if (! $rank) {
            $this->rankId = null;

            return;
        }

        // $this->authorize('delete', $rank);

        $rank->delete();

        $this->rankId = null;

        $this->dispatch('rankWasDeleted', __('Rank was deleted!'));
    }

    public function render()
    {
        return view('services::livewire.services.ranks.delete-rank');
    }
}
