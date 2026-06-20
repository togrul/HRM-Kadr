<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Enums\OrderStatusEnum;
use App\Models\EmployeeRequestChangeRequest;
use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Modules\Personnel\Application\Services\MyHr\Review\SelfServiceRequestPatchService;
use App\Modules\Personnel\Application\Services\MyHr\Review\SelfServiceReviewAuthorizationService;
use App\Modules\Personnel\Application\Services\MyHr\Review\SelfServiceReviewNotificationService;
use App\Modules\Personnel\Application\Services\MyHr\Review\SelfServiceVacationOrderBinderService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MyHrRequestReviewService
{
    public function __construct(
        private readonly SelfServiceReviewAuthorizationService $authorization,
        private readonly SelfServiceReviewNotificationService $notifications,
        private readonly SelfServiceRequestPatchService $requestPatchService,
        private readonly SelfServiceVacationOrderBinderService $vacationOrderBinder,
    ) {}

    public function canReviewLeave(Leave $leave, User $reviewer): bool
    {
        return $this->authorization->canReviewLeave($leave, $reviewer);
    }

    public function canReviewVacation(PersonnelVacation $vacation, User $reviewer): bool
    {
        return $this->authorization->canReviewVacation($vacation, $reviewer);
    }

    public function canReviewBusinessTrip(PersonnelBusinessTrip $trip, User $reviewer): bool
    {
        return $this->authorization->canReviewBusinessTrip($trip, $reviewer);
    }

    public function canReviewCorrection(EmployeeRequestChangeRequest $change, User $reviewer): bool
    {
        return $this->authorization->canReviewCorrection($change, $reviewer);
    }

    public function approveLeave(Leave $leave, User $reviewer, ?string $note = null): void
    {
        DB::transaction(function () use ($leave, $reviewer, $note): void {
            $locked = Leave::query()->lockForUpdate()->findOrFail($leave->id);

            if (! $this->canReviewLeave($locked, $reviewer)) {
                throw new RuntimeException('Leave is no longer pending self-service review.');
            }

            $now = now();

            $locked->forceFill([
                'status_id' => OrderStatusEnum::APPROVED->value,
                'approved_at' => $now,
                'approved_by' => $reviewer->personnel?->id ?: $reviewer->id,
            ])->save();

            $locked->logs()->create([
                'status_id' => OrderStatusEnum::APPROVED->value,
                'changed_by' => $reviewer->personnel?->id ?: $reviewer->id,
                'comment' => $note,
                'changed_at' => $now,
            ]);
        });

        $this->notifications->notifyRequestReviewed(
            $leave->submittedBy,
            $leave->leaveType?->name ?: __('personnel::my_hr.requests.types.leave'),
            true,
            $note
        );
    }

    public function rejectLeave(Leave $leave, User $reviewer, ?string $note = null): void
    {
        DB::transaction(function () use ($leave, $reviewer, $note): void {
            $locked = Leave::query()->lockForUpdate()->findOrFail($leave->id);

            if (! $this->canReviewLeave($locked, $reviewer)) {
                throw new RuntimeException('Leave is no longer pending self-service review.');
            }

            $locked->forceFill([
                'status_id' => OrderStatusEnum::CANCELLED->value,
                'approved_at' => null,
                'approved_by' => null,
            ])->save();

            $locked->logs()->create([
                'status_id' => OrderStatusEnum::CANCELLED->value,
                'changed_by' => $reviewer->personnel?->id ?: $reviewer->id,
                'comment' => $note,
                'changed_at' => now(),
            ]);
        });

        $this->notifications->notifyRequestReviewed(
            $leave->submittedBy,
            $leave->leaveType?->name ?: __('personnel::my_hr.requests.types.leave'),
            false,
            $note
        );
    }

    public function approveVacation(PersonnelVacation $vacation, User $reviewer, ?string $note = null): void
    {
        $this->updateVacationStatus($vacation, $reviewer, 'approved', $note);
    }

    public function rejectVacation(PersonnelVacation $vacation, User $reviewer, ?string $note = null): void
    {
        $this->updateVacationStatus($vacation, $reviewer, 'rejected', $note);
    }

    public function approveBusinessTrip(PersonnelBusinessTrip $trip, User $reviewer, ?string $note = null): void
    {
        $this->updateBusinessTripStatus($trip, $reviewer, 'approved', $note);
    }

    public function rejectBusinessTrip(PersonnelBusinessTrip $trip, User $reviewer, ?string $note = null): void
    {
        $this->updateBusinessTripStatus($trip, $reviewer, 'rejected', $note);
    }

    public function bindOperationalVacationOrder(PersonnelVacation $vacation, User $reviewer): void
    {
        $this->vacationOrderBinder->bind($vacation, $reviewer);
    }

    public function approveCorrection(EmployeeRequestChangeRequest $change, User $reviewer, ?string $note = null): void
    {
        DB::transaction(function () use ($change, $reviewer, $note): void {
            $locked = EmployeeRequestChangeRequest::query()
                ->with('requestable')
                ->lockForUpdate()
                ->findOrFail($change->id);

            if ($locked->status !== 'pending') {
                throw new RuntimeException('Correction request is no longer pending.');
            }

            $requestable = $locked->requestable;
            if (! $requestable) {
                throw new RuntimeException('Requestable model is missing.');
            }

            $this->requestPatchService->apply($requestable, (array) $locked->proposed_patch);

            $locked->forceFill([
                'status' => 'approved',
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
                'applied_at' => now(),
            ])->save();
        });

        $this->notifications->notifyCorrectionReviewed($change->requestedBy, $change->requestable_type, true, $note);
    }

    public function rejectCorrection(EmployeeRequestChangeRequest $change, User $reviewer, ?string $note = null): void
    {
        $change->forceFill([
            'status' => 'rejected',
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $this->notifications->notifyCorrectionReviewed($change->requestedBy, $change->requestable_type, false, $note);
    }

    private function updateVacationStatus(PersonnelVacation $vacation, User $reviewer, string $status, ?string $note): void
    {
        DB::transaction(function () use ($vacation, $reviewer, $status, $note): void {
            $locked = PersonnelVacation::query()->lockForUpdate()->findOrFail($vacation->id);

            if ($status === 'approved' || $status === 'rejected') {
                if (! $this->canReviewVacation($locked, $reviewer)) {
                    throw new RuntimeException('Vacation is no longer pending self-service review.');
                }
            }

            $locked->forceFill([
                'approval_status' => $status,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            if ($status === 'approved' && blank($locked->order_no)) {
                $this->vacationOrderBinder->bind($locked, $reviewer);
            }
        });

        $this->notifications->notifyRequestReviewed(
            $vacation->submittedBy,
            __('personnel::my_hr.requests.types.vacation'),
            $status === 'approved',
            $note
        );
    }

    private function updateBusinessTripStatus(PersonnelBusinessTrip $trip, User $reviewer, string $status, ?string $note): void
    {
        DB::transaction(function () use ($trip, $reviewer, $status, $note): void {
            $locked = PersonnelBusinessTrip::query()->lockForUpdate()->findOrFail($trip->id);

            if ($status === 'approved' || $status === 'rejected') {
                if (! $this->canReviewBusinessTrip($locked, $reviewer)) {
                    throw new RuntimeException('Business trip is no longer pending self-service review.');
                }
            }

            $locked->forceFill([
                'approval_status' => $status,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();
        });

        $this->notifications->notifyRequestReviewed(
            $trip->submittedBy,
            __('personnel::my_hr.requests.types.business_trip'),
            $status === 'approved',
            $note
        );
    }
}
