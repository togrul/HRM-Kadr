<?php

namespace App\Modules\Services\Livewire\Ranks;

use App\Models\Rank;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteRank extends Component
{
    use AuthorizesRequests;
    use AuthorizesSettingsAccess;

    #[Locked]
    public ?int $rankId = null;

    #[On('setDeleteRank')]
    public function setDeleteRank($rankId): void
    {
        $rank = Rank::query()
            ->select('id')
            ->find($rankId);

        if (! $rank) {
            $this->rankId = null;

            return;
        }

        if ($this->refuseInUse((int) $rank->id)) {
            return;
        }

        $this->rankId = (int) $rank->id;

        $this->dispatch('deleteRankWasSet');
    }

    public function deleteRank(): void
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

        if ($this->refuseInUse((int) $rank->id)) {
            $this->rankId = null;

            return;
        }

        $rank->delete();

        $this->rankId = null;

        $this->dispatch('rankWasDeleted', __('services::ranks.messages.deleted'));
    }

    /**
     * A rank is in use while any personnel record points at it (these tables hold a foreign key to ranks).
     */
    private function refuseInUse(int $rankId): bool
    {
        $inUse = collect(['personnel_ranks', 'personnel_military_services', 'personnel_contracts'])
            ->contains(fn (string $table): bool => DB::table($table)->where('rank_id', $rankId)->exists());

        if ($inUse) {
            $this->dispatch('notify', type: 'error', message: __('services::ranks.messages.in_use'));
        }

        return $inUse;
    }

    public function render(): View
    {
        return view('services::livewire.services.ranks.delete-rank');
    }
}
