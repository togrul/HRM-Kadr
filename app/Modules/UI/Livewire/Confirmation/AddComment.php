<?php

namespace App\Modules\UI\Livewire\Confirmation;

use App\Models\Leave;
use RuntimeException;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\DB;

class AddComment extends Component
{
    public string $comment = '';

    private function setPermitStatus(int $id, OrderStatusEnum $toStatus): void
    {
        DB::transaction(function () use ($id, $toStatus) {
            $userId = auth()->id();
            $now    = now();

            // Yekun statuslar: artıq APPROVED və ya CANCELLED-disə dəyişməyə icazə vermirik
            $finalStatuses = [
                OrderStatusEnum::APPROVED->value,
                OrderStatusEnum::CANCELLED->value,
            ];

            $leave = Leave::lockForUpdate()->findOrFail($id);

            if (in_array((int) $leave->status_id, $finalStatuses, true)) {
                throw new RuntimeException('This leave request is already finalized.');
            }

            $leave->status_id = $toStatus->value;

            if ($toStatus === OrderStatusEnum::APPROVED) {
                $leave->approved_at = $now;
                $leave->approved_by = $userId;
            }

            $leave->save();

            $leave->logs()->create([
                'status_id'  => $toStatus->value,
                'changed_by' => $userId,
                'comment'    => $this->comment,
                'changed_at' => $now,
            ]);
        });
    }

    public function confirmComment(?string $action = null, ?int $leaveId = null): void
    {
        $this->setPermitStatus(
            $leaveId,
            OrderStatusEnum::label($action)
        );

        if ($action === OrderStatusEnum::APPROVED->name) {
            $successEvent = 'leaveApproved';
            $successMsg   =  __('Leave was approved successfully!');
        }
        else {
            $successEvent = 'leaveRejected';
            $successMsg   =  __('Leave was rejected successfully!');
        }

        $this->reset('comment');
        $this->dispatch($successEvent, $successMsg);
    }

    public function render()
    {
        return view('ui::livewire.confirmation.add-comment');
    }
}
