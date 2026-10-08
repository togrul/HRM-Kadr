<?php

namespace App\Modules\UI\Livewire\Confirmation;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Services\Absence\AbsenceOverlapException;
use App\Services\Absence\AbsenceOverlapGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use RuntimeException;

class AddComment extends Component
{
    public string $comment = '';

    private function setPermitStatus(int $id, OrderStatusEnum $toStatus): void
    {
        DB::transaction(function () use ($id, $toStatus) {
            $userId = auth()->id();
            $now = now();

            // Yekun statuslar: artıq APPROVED və ya CANCELLED-disə dəyişməyə icazə vermirik
            $finalStatuses = [
                OrderStatusEnum::APPROVED->value,
                OrderStatusEnum::CANCELLED->value,
            ];

            $leave = Leave::lockForUpdate()->findOrFail($id);

            if (in_array((int) $leave->status_id, $finalStatuses, true)) {
                throw new RuntimeException('This leave request is already finalized.');
            }

            // An approved leave must not overlap another live leave, vacation or business trip.
            if ($toStatus === OrderStatusEnum::APPROVED && ($period = $leave->absencePeriod()) !== null) {
                app(AbsenceOverlapGuard::class)->assertNoOverlap((string) $leave->tabel_no, $period);
            }

            $leave->status_id = $toStatus->value;

            if ($toStatus === OrderStatusEnum::APPROVED) {
                $leave->approved_at = $now;
                $leave->approved_by = $userId;
            } else {
                $leave->approved_at = null;
                $leave->approved_by = null;
            }

            $leave->save();

            $leave->logs()->create([
                'status_id' => $toStatus->value,
                'changed_by' => $userId,
                'comment' => $this->comment,
                'changed_at' => $now,
            ]);
        });
    }

    public function confirmComment(?string $action = null, ?int $leaveId = null): void
    {
        try {
            $this->setPermitStatus(
                $leaveId,
                OrderStatusEnum::label($action)
            );
        } catch (AbsenceOverlapException $exception) {
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());

            return;
        }

        if ($action === OrderStatusEnum::APPROVED->name) {
            $successEvent = 'leaveApproved';
            $successMsg = __('leaves::common.messages.leave_approved');
        } else {
            $successEvent = 'leaveRejected';
            $successMsg = __('leaves::common.messages.leave_rejected');
        }

        $this->reset('comment');
        $this->dispatch($successEvent, $successMsg);
    }

    public function render(): View
    {
        return view('ui::livewire.confirmation.add-comment');
    }
}
