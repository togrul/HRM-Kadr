<?php

namespace App\Modules\Staff\Livewire;

use App\Models\StaffSchedule;
use Illuminate\Contracts\View\View;
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
    public function setDeleteStaff($staffId): void
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

    public function deleteStaff(): void
    {
        if (empty($this->staffIds)) {
            return;
        }

        $this->authorize('delete-staff', $this->staffIds);

        StaffSchedule::query()->whereIn('id', $this->staffIds)->delete();

        $this->staffIds = null;

        $this->dispatch('staffWasDeleted', __('staff::common.messages.staff_deleted'));
    }

    public function render(): View
    {
        return view('staff::livewire.staff-schedule.delete-staff');
    }
}
