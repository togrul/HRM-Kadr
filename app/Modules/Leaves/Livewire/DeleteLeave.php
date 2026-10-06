<?php

namespace App\Modules\Leaves\Livewire;

use App\Models\Leave;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteLeave extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $leaveId = null;

    #[On('setDeleteLeave')]
    public function setDeleteLeave($leaveId): void
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

    public function deleteLeave(): void
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

        $this->dispatch('leaveWasDeleted', __('leaves::common.messages.leave_deleted'));
    }

    public function render(): View
    {
        return view('leaves::livewire.leaves.delete-leave');
    }
}
