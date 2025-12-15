<?php

namespace App\Modules\Staff\Livewire;

use App\Models\StaffSchedule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteStaff extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?array $staffIds = null;

    #[On('setDeleteStaff')]
    public function setDeleteStaff($staffId)
    {
        $ids = StaffSchedule::query()
            ->where('structure_id', $staffId)
            ->pluck('id')
            ->all();

        if (empty($ids)) {
            $this->staffIds = null;

            return;
        }

        $this->staffIds = $ids;

        $this->dispatch('deleteStaffWasSet');
    }

    public function deleteStaff()
    {
        if (empty($this->staffIds)) {
            return;
        }

        $this->authorize('delete-staff', $this->staffIds);

        StaffSchedule::query()->whereIn('id', $this->staffIds)->delete();

        $this->staffIds = null;

        $this->dispatch('staffWasDeleted', __('Staff was deleted!'));
    }

    public function render()
    {
        return view('staff::livewire.staff-schedule.delete-staff');
    }
}
