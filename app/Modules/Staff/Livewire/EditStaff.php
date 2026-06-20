<?php

namespace App\Modules\Staff\Livewire;

use App\Modules\Staff\Support\Traits\StaffCrud;
use App\Models\StaffSchedule;
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
        $this->staff = $this->getStaffs()[$this->staffModel]->toArray();
        $this->title = __('staff::common.titles.edit_staff').'( '.$this->staff[0]['structure']['name'].' )';
        $this->syncComputedStaffRows();
    }

    public function store()
    {
        $this->syncComputedStaffRows();
        $this->validate();
        $existingData = $this->getStaffs()[$this->staffModel]->toArray();

        foreach ($this->staff as $sta) {
            $data = $sta;
            unset($data['id']);
            unset($data['structure']);
            unset($data['position']);
            unset($data['hide_position']);
            if (array_key_exists('id', $sta)) {
                StaffSchedule::find($sta['id'])->update($data);
            } else {
                StaffSchedule::create($data);
            }
            $updatedDataIds = collect($this->staff)->pluck('id')->filter();

            $removedIds = collect($existingData)->pluck('id')->diff($updatedDataIds);

            StaffSchedule::destroy($removedIds);

        }

        $this->dispatch('staffAdded', __('staff::common.messages.staff_updated'));
    }
}
