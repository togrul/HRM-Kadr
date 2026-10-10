<?php

namespace App\Modules\UI\Livewire\Confirmation;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Services\Absence\AbsenceOverlapException;
use App\Services\Absence\AbsenceOverlapGuard;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Auth\Access\AuthorizationException;
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

            $this->authorizeDecision($leave);

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

    /**
     * Təsdiq/rədd qərarını kim verə bilər (yalnız server tərəfində, kilidlənmiş qeyd üzərində):
     *  - icazə istifadəçinin struktur görünürlüyündə olmalı və onu görə bilməlidir (view);
     *  - icazə gözləmədə olmalı və istifadəçi təsdiqləyici ola bilməlidir (canBeApprovedBy);
     *  - heç kim öz icazəsinə qərar verə bilməz;
     *  - təsdiq marşrutu icazəni konkret olaraq bu istifadəçinin əməkdaşına təyin edibsə bu
     *    kifayətdir, əks halda `approve-leaves` + `update` hüququ tələb olunur;
     *  - xəstəlik vərəqəsinə bağlı icazə yalnız reyestrdə dəyişir.
     *
     * @throws AuthorizationException
     */
    private function authorizeDecision(Leave $leave): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->can('view', $leave) || $leave->isManagedBySickCertificate()) {
            throw new AuthorizationException(__('leaves::common.messages.approval_forbidden'));
        }

        $personnelId = app(UserPersonnelLinkResolver::class)->resolve($user);
        // Öz icazəsi yoxlaması bağın özünə baxır (əməkdaşın aktivliyindən asılı deyil).
        $linkedPersonnelId = UserPersonnelLink::query()->where('user_id', $user->getKey())->value('personnel_id') ?? $personnelId;
        $ownTabelNo = $linkedPersonnelId !== null
            ? Personnel::withTrashed()->whereKey($linkedPersonnelId)->value('tabel_no')
            : null;

        if ($ownTabelNo !== null && (string) $ownTabelNo === (string) $leave->tabel_no) {
            throw new AuthorizationException(__('leaves::common.messages.approval_own_leave'));
        }

        if (! $leave->canBeApprovedBy($user)) {
            throw new AuthorizationException(__('leaves::common.messages.approval_forbidden'));
        }

        $isAssignedApprover = $leave->assigned_to !== null
            && $personnelId !== null
            && (int) $leave->assigned_to === $personnelId;

        if (! $isAssignedApprover && ! ($user->can('approve-leaves') && $user->can('update', $leave))) {
            throw new AuthorizationException(__('leaves::common.messages.approval_forbidden'));
        }
    }

    public function confirmComment(?string $action = null, ?int $leaveId = null): void
    {
        // Yalnız təsdiq və ya rədd; digər status (məs. PENDING) bu yoldan qoyulmur.
        if ($leaveId === null || ! in_array($action, [OrderStatusEnum::APPROVED->name, OrderStatusEnum::CANCELLED->name], true)) {
            throw new AuthorizationException(__('leaves::common.messages.approval_forbidden'));
        }

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
