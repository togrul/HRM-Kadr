<?php

namespace App\Modules\Leaves\Livewire;

use App\Models\Leave;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DeleteLeave extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $leaveId = null;

    #[On('setDeleteLeave')]
    public function setDeleteLeave($leaveId)
    {
        $leave = Leave::query()->select('id')->find($leaveId);

        if (! $leave) {
            $this->leaveId = null;
            return;
        }

        $this->authorize('delete', $leave);

        $this->leaveId = (int) $leave->id;

        $this->dispatch('deleteLeaveWasSet');
    }

    public function deleteLeave()
    {
        if (! $this->leaveId) {
            return;
        }

        $leave = Leave::query()->select('id')->find($this->leaveId);

        if (! $leave) {
            $this->leaveId = null;
            return;
        }

        $this->authorize('delete', $leave);

        $leave->delete();

        $this->leaveId = null;

        $this->dispatch('leaveWasDeleted', __('Leave was deleted!'));
    }

    public function render()
    {
        return view('leaves::livewire.leaves.delete-leave');
    }
}
