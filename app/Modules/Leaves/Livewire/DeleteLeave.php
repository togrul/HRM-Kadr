<?php

namespace App\Modules\Leaves\Livewire;

use App\Models\Leave;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

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
        if (! $this->leaveId) {
            return;
        }

        Leave::query()->whereKey($this->leaveId)->delete();

        $this->leaveId = null;

        $this->dispatch('leaveWasDeleted', __('Leave was deleted!'));
    }

    public function render()
    {
        return view('leaves::livewire.leaves.delete-leave');
    }
}
