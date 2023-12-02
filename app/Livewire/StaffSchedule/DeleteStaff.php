<?php

namespace App\Livewire\StaffSchedule;

use App\Models\StaffSchedule;
use Livewire\Component;

class DeleteStaff extends Component
{
    public ?StaffSchedule $staff;

    protected $listeners = ['setDeleteStaff'];

    public function setDeleteStaff($staffId)
    {
        $this->staff = StaffSchedule::findOrFail($staffId);

        $this->dispatch('deleteStaffWasSet');
    }

    public function deleteStaff()
    {
        // $this->authorize('delete',$this->employee);

        StaffSchedule::destroy($this->staff->id);

        $this->staff = null;

        $this->dispatch('staffWasDeleted' , __('Staff was deleted!'));
    }

    public function render()
    {
        return view('livewire.staff-schedule.delete-staff');
    }
}
