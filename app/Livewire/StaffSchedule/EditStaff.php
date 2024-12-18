<?php

namespace App\Livewire\StaffSchedule;

use App\Livewire\Traits\StaffCrud;
use App\Models\StaffSchedule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EditStaff extends Component
{
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
        $this->title = __('Edit').'( '.$this->staff[0]['structure']['name'].' )';
        $this->hidePosition = is_null($this->staff[0]['structure']['parent_id']);
    }

    public function store()
    {
        $this->validate();
        $existingData = $this->getStaffs()[$this->staffModel]->toArray();

        foreach ($this->staff as $sta) {
            $data = $sta;
            unset($data['id']);
            unset($data['structure']);
            unset($data['position']);
            if (array_key_exists('id', $sta)) {
                StaffSchedule::find($sta['id'])->update($data);
            } else {
                StaffSchedule::create($data);
            }
            $updatedDataIds = collect($this->staff)->pluck('id')->filter();

            $removedIds = collect($existingData)->pluck('id')->diff($updatedDataIds);

            StaffSchedule::destroy($removedIds);

        }

        $this->dispatch('staffAdded', __('Staff was updated successfully!'));
    }
}
