<?php

namespace App\Modules\Staff\Livewire;

use App\Models\StaffSchedule;
use App\Modules\Staff\Support\Traits\StaffCrud;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EditStaff extends Component
{
    use AuthorizesRequests;
    use StaffCrud;

    #[Computed]
    public function getStaffs()
    {
        return StaffSchedule::with(['structure', 'position'])
            ->where('structure_id', $this->staffModel)
            ->get()
            ->groupBy('structure_id');
    }

    public function mount()
    {
        $this->authorize('edit-staff', $this->staffModel);

        // Defensive: a structure with no staff-schedule rows of its own has nothing to
        // edit (the tree only exposes edit on staffed nodes, but guard regardless).
        $group = $this->getStaffs()->get($this->staffModel);
        if ($group === null || $group->isEmpty()) {
            $this->staff = [];
            $this->title = __('staff::common.titles.edit_staff');

            return;
        }

        $this->staff = $group->toArray();
        $this->title = __('staff::common.titles.edit_staff').'( '.$this->staff[0]['structure']['name'].' )';
        $this->syncComputedStaffRows();
    }

    public function store(): void
    {
        $this->syncComputedStaffRows();
        $this->validate();
        $existingData = $this->getStaffs()[$this->staffModel]->toArray();

        // Compute the deletion set once, before the upsert loop — recomputing it on
        // every iteration (and re-running destroy) was redundant query work.
        $keptIds = collect($this->staff)->pluck('id')->filter();
        $removedIds = collect($existingData)->pluck('id')->diff($keptIds);

        if ($removedIds->isNotEmpty()) {
            StaffSchedule::destroy($removedIds);
        }

        foreach ($this->staff as $sta) {
            $data = $sta;
            unset($data['id'], $data['structure'], $data['position'], $data['hide_position']);

            if (array_key_exists('id', $sta)) {
                // findOrFail guards against a stale/forged id that would otherwise fatal on null->update().
                StaffSchedule::findOrFail($sta['id'])->update($data);
            } else {
                StaffSchedule::create($data);
            }
        }

        $this->dispatch('staffAdded', __('staff::common.messages.staff_updated'));
    }
}
