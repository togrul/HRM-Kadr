<?php

namespace App\Livewire\Outside;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Locked;
use App\Models\Leave;

class DeleteLeave extends Component
{
    #[Locked]
    public ?int $leaveId = null;

    #[On('setDeleteLeave')]
    public function setDeleteLeave($leaveId)
    {
        $id = Leave::query()->whereKey($leaveId)->value('id');

        if (! $id) {
            $this->leaveId = null;

            return;
        }

        $this->leaveId = (int) $id;

        $this->dispatch('deleteLeaveWasSet');
    }

    public function deleteLeave()
    {
        // $this->authorize('delete-leave',$this->leave);
        if (! $this->leaveId) {
            return;
        }

        Leave::query()->whereKey($this->leaveId)->delete();

        $this->leaveId = null;

        $this->dispatch('leaveWasDeleted', __('Leave was deleted!'));
    }

    public function render()
    {
        return view('livewire.outside.delete-leave');
    }
}
