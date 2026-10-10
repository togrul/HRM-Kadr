<?php

namespace App\Modules\Leaves\Livewire;

use App\Models\Leave;
use App\Modules\Leaves\Application\Services\LeaveRecordService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
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
        $leave = Leave::query()->select(['id', 'tabel_no'])->find($leaveId);

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

        $leave = Leave::query()->find($this->leaveId);

        if (! $leave) {
            $this->leaveId = null;

            return;
        }

        $this->authorize('delete', $leave);

        // Order / certificate leaves and closed pay months are refused by the service.
        try {
            app(LeaveRecordService::class)->delete($leave, auth()->user());
        } catch (ValidationException $exception) {
            $this->leaveId = null;
            $this->dispatch('addError', collect($exception->errors())->flatten()->first());

            return;
        }

        $this->leaveId = null;

        $this->dispatch('leaveWasDeleted', __('leaves::common.messages.leave_deleted'));
    }

    public function render(): View
    {
        return view('leaves::livewire.leaves.delete-leave');
    }
}
