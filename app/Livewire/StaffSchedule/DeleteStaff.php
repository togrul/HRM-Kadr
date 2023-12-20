<?php

namespace App\Livewire\StaffSchedule;

use App\Models\StaffSchedule;
use Livewire\Component;

class DeleteStaff extends Component
{
    public $staff;

    protected $listeners = ['setDeleteStaff'];

    public function setDeleteStaff($staffId)
    {
        $this->staff = StaffSchedule::where('structure_id',$staffId)->pluck('id');

        $this->dispatch('deleteStaffWasSet');
    }

    public function deleteStaff()
    {
        // $this->authorize('delete',$this->employee);
        StaffSchedule::destroy($this->staff);

        $this->staff = null;

        $this->dispatch('staffWasDeleted' , __('Staff was deleted!'));
    }

    public function render()
    {
        return view('livewire.staff-schedule.delete-staff');
    }
}
