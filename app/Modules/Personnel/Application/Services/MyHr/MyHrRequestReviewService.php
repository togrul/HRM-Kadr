<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Enums\OrderStatusEnum;
use App\Models\Component;
use App\Models\EmployeeRequestChangeRequest;
use App\Models\Leave;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Services\UserPersonnelLinkResolver;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class MyHrRequestReviewService
{
    public function __construct(
        private readonly UserPersonnelLinkResolver $userPersonnelLinkResolver,
    ) {}

    public function canReviewLeave(Leave $leave, User $reviewer): bool
    {
        if ((string) $leave->submission_source !== 'employee_self_service' || ! $leave->isPending) {
            return false;
        }

        if ($this->canReviewAll($reviewer)) {
            return true;
        }

        $reviewerPersonnelId = $this->reviewerPersonnelId($reviewer);
        if (! $reviewerPersonnelId) {
            return false;
        }

        return in_array($reviewerPersonnelId, array_filter([
            (int) $leave->assigned_to,
            (int) $leave->fallback_approver_personnel_id,
        ]), true);
    }

    public function canReviewVacation(PersonnelVacation $vacation, User $reviewer): bool
    {
        if ((string) $vacation->submission_source !== 'employee_self_service' || (string) $vacation->approval_status !== 'pending') {
            return false;
        }

        if ($this->canReviewAll($reviewer)) {
            return true;
        }

        $reviewerPersonnelId = $this->reviewerPersonnelId($reviewer);
        if (! $reviewerPersonnelId) {
            return false;
        }

        return in_array($reviewerPersonnelId, array_filter([
            (int) $vacation->approver_personnel_id,
            (int) $vacation->fallback_approver_personnel_id,
        ]), true);
    }

    public function canReviewBusinessTrip(PersonnelBusinessTrip $trip, User $reviewer): bool
    {
        if ((string) $trip->submission_source !== 'employee_self_service' || (string) $trip->approval_status !== 'pending') {
            return false;
        }

        if ($this->canReviewAll($reviewer)) {
            return true;
        }

        $reviewerPersonnelId = $this->reviewerPersonnelId($reviewer);
        if (! $reviewerPersonnelId) {
            return false;
        }

        return in_array($reviewerPersonnelId, array_filter([
            (int) $trip->approver_personnel_id,
            (int) $trip->fallback_approver_personnel_id,
        ]), true);
    }

    public function canReviewCorrection(EmployeeRequestChangeRequest $change, User $reviewer): bool
    {
        if ($change->status !== 'pending') {
            return false;
        }

        if ($this->canReviewAll($reviewer)) {
            return true;
        }

        $reviewerPersonnelId = $this->reviewerPersonnelId($reviewer);
        if (! $reviewerPersonnelId) {
            return false;
        }

        $change->loadMissing('requestable');
        $requestable = $change->requestable;

        return match (true) {
            $requestable instanceof Leave => in_array($reviewerPersonnelId, array_filter([
                (int) $requestable->assigned_to,
                (int) $requestable->fallback_approver_personnel_id,
            ]), true),
            $requestable instanceof PersonnelVacation => in_array($reviewerPersonnelId, array_filter([
                (int) $requestable->approver_personnel_id,
                (int) $requestable->fallback_approver_personnel_id,
            ]), true),
            $requestable instanceof PersonnelBusinessTrip => in_array($reviewerPersonnelId, array_filter([
                (int) $requestable->approver_personnel_id,
                (int) $requestable->fallback_approver_personnel_id,
            ]), true),
            default => false,
        };
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

        $this->notifyRequester(
            $leave->submittedBy,
            'self_service_request_reviewed',
            [
                'action' => 'selfServiceRequestReviewed',
                'category' => __('personnel::my_hr.review.notifications.category'),
                'message' => __('personnel::my_hr.review.notifications.approved'),
                'name' => $leave->leaveType?->name ?: __('personnel::my_hr.requests.types.leave'),
                'body' => $note ?: __('personnel::my_hr.review.notifications.default_approved_body'),
            ]
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

        $this->notifyRequester(
            $leave->submittedBy,
            'self_service_request_reviewed',
            [
                'action' => 'selfServiceRequestReviewed',
                'category' => __('personnel::my_hr.review.notifications.category'),
                'message' => __('personnel::my_hr.review.notifications.rejected'),
                'name' => $leave->leaveType?->name ?: __('personnel::my_hr.requests.types.leave'),
                'body' => $note ?: __('personnel::my_hr.review.notifications.default_rejected_body'),
            ]
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

            $this->applyPatch($requestable, (array) $locked->proposed_patch);

            $locked->forceFill([
                'status' => 'approved',
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
                'applied_at' => now(),
            ])->save();
        });

        $this->notifyRequester(
            $change->requestedBy,
            'self_service_correction_reviewed',
            [
                'action' => 'selfServiceCorrectionReviewed',
                'category' => __('personnel::my_hr.review.notifications.category'),
                'message' => __('personnel::my_hr.review.notifications.correction_approved'),
                'name' => $this->requestableLabel($change->requestable_type),
                'body' => $note ?: __('personnel::my_hr.review.notifications.default_correction_approved_body'),
            ]
        );
    }

    public function rejectCorrection(EmployeeRequestChangeRequest $change, User $reviewer, ?string $note = null): void
    {
        $change->forceFill([
            'status' => 'rejected',
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        $this->notifyRequester(
            $change->requestedBy,
            'self_service_correction_reviewed',
            [
                'action' => 'selfServiceCorrectionReviewed',
                'category' => __('personnel::my_hr.review.notifications.category'),
                'message' => __('personnel::my_hr.review.notifications.correction_rejected'),
                'name' => $this->requestableLabel($change->requestable_type),
                'body' => $note ?: __('personnel::my_hr.review.notifications.default_correction_rejected_body'),
            ]
        );
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
                $this->bindOperationalVacationOrder($locked, $reviewer);
            }
        });

        $this->notifyRequester(
            $vacation->submittedBy,
            'self_service_request_reviewed',
            [
                'action' => 'selfServiceRequestReviewed',
                'category' => __('personnel::my_hr.review.notifications.category'),
                'message' => $status === 'approved'
                    ? __('personnel::my_hr.review.notifications.approved')
                    : __('personnel::my_hr.review.notifications.rejected'),
                'name' => __('personnel::my_hr.requests.types.vacation'),
                'body' => $note ?: ($status === 'approved'
                    ? __('personnel::my_hr.review.notifications.default_approved_body')
                    : __('personnel::my_hr.review.notifications.default_rejected_body')),
            ]
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

        $this->notifyRequester(
            $trip->submittedBy,
            'self_service_request_reviewed',
            [
                'action' => 'selfServiceRequestReviewed',
                'category' => __('personnel::my_hr.review.notifications.category'),
                'message' => $status === 'approved'
                    ? __('personnel::my_hr.review.notifications.approved')
                    : __('personnel::my_hr.review.notifications.rejected'),
                'name' => __('personnel::my_hr.requests.types.business_trip'),
                'body' => $note ?: ($status === 'approved'
                    ? __('personnel::my_hr.review.notifications.default_approved_body')
                    : __('personnel::my_hr.review.notifications.default_rejected_body')),
            ]
        );
    }

    public function bindOperationalVacationOrder(PersonnelVacation $vacation, User $reviewer): void
    {
        $vacation->loadMissing([
            'personnel.latestRank.rank',
            'personnel.yearlyVacation',
        ]);

        $personnel = $vacation->personnel;
        $orderType = $this->resolveOrCreateVacationOperationalOrderType();

        if (! $orderType || ! $orderType->order) {
            throw new RuntimeException('Vacation operational order type is not configured.');
        }

        $orderNo = $this->generateOperationalOrderNo('VAC');
        $orderLog = OrderLog::query()->create([
            'order_id' => $orderType->order_id,
            'order_type_id' => $orderType->id,
            'order_no' => $orderNo,
            'given_date' => now()->toDateString(),
            'given_by' => $reviewer->name,
            'given_by_rank' => $reviewer->personnel?->latestRank?->rank?->name ?: 'HR',
            'description' => null,
            'status_id' => OrderStatusEnum::APPROVED->value,
            'creator_id' => $reviewer->id,
        ]);

        $component = $this->resolveOrCreateVacationOperationalComponent($orderType);

        if ($component && $personnel) {
            $rowNumber = 0;

            $orderLog->components()->attach([
                $component->id => ['row_number' => $rowNumber],
            ]);

            $orderLog->attributes()->create([
                'component_id' => $component->id,
                'row_number' => $rowNumber,
                'attributes' => [
                    '$fullname' => ['id' => null, 'value' => $personnel->fullname],
                    '$rank' => ['id' => null, 'value' => $personnel->latestRank?->rank?->name],
                    '$location' => ['id' => null, 'value' => (string) $vacation->vacation_places],
                    '$start_date' => ['id' => null, 'value' => $vacation->start_date?->format('Y-m-d')],
                    '$end_date' => ['id' => null, 'value' => $vacation->end_date?->format('Y-m-d')],
                    '$days' => ['id' => null, 'value' => (int) $vacation->duration],
                ],
            ]);

            $orderLog->personnels()->attach([
                $personnel->tabel_no => ['component_id' => $component->id],
            ]);

            $currentYearlyVacation = $personnel->yearlyVacation
                ->firstWhere('year', (int) $vacation->start_date?->year)
                ?? $personnel->yearlyVacation->first();

            $vacationDaysTotal = (int) ($currentYearlyVacation?->vacation_days_total ?? $vacation->vacation_days_total ?? 0);
            $remainingDaysBefore = (int) ($currentYearlyVacation?->remaining_days ?? $vacation->remaining_days ?? 0);
            $remainingDaysAfter = max(0, $remainingDaysBefore - (int) $vacation->duration);

            if ($currentYearlyVacation) {
                $currentYearlyVacation->forceFill([
                    'remaining_days' => $remainingDaysAfter,
                ])->save();
            }

            $vacation->forceFill([
                'order_no' => $orderNo,
                'order_date' => now(),
                'order_given_by' => $reviewer->name,
                'vacation_days_total' => $vacationDaysTotal,
                'remaining_days' => $remainingDaysAfter,
            ])->save();

            $vacation->refresh();

            return;
        }

        $vacation->forceFill([
            'order_no' => $orderNo,
            'order_date' => now(),
            'order_given_by' => $reviewer->name,
        ])->save();
    }

    private function generateOperationalOrderNo(string $prefix): string
    {
        do {
            $candidate = sprintf('%s-%s', $prefix, Str::upper(Str::random(10)));
        } while (OrderLog::query()->where('order_no', $candidate)->exists());

        return $candidate;
    }

    private function resolveVacationOperationalOrderType(): ?OrderType
    {
        $byBlade = OrderType::query()
            ->with('order')
            ->whereHas('order', fn ($query) => $query->where('blade', Order::BLADE_VACATION))
            ->orderBy('id')
            ->first();

        if ($byBlade) {
            return $byBlade;
        }

        return OrderType::query()
            ->with('order')
            ->whereHas('order', fn ($query) => $query->where('order_model', PersonnelVacation::class))
            ->orderBy('id')
            ->first();
    }

    private function resolveOrCreateVacationOperationalOrderType(): ?OrderType
    {
        $existing = $this->resolveVacationOperationalOrderType();

        if ($existing) {
            return $existing;
        }

        $order = $this->resolveVacationOperationalOrder();

        if (! $order) {
            return null;
        }

        return OrderType::query()->firstOrCreate(
            ['order_id' => $order->id],
            ['name' => $order->name ?: 'Məzuniyyət əmri']
        )->loadMissing('order');
    }

    private function resolveVacationOperationalOrder(): ?Order
    {
        $byBlade = Order::query()
            ->where('blade', Order::BLADE_VACATION)
            ->orderBy('id')
            ->first();

        if ($byBlade) {
            return $byBlade;
        }

        $byModel = Order::query()
            ->where('order_model', PersonnelVacation::class)
            ->orderBy('id')
            ->first();

        if ($byModel) {
            return $byModel;
        }

        return Order::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%məzuniyyət%')
                    ->orWhere('name', 'like', '%mezuniyyet%')
                    ->orWhere('name', 'like', '%vacation%')
                    ->orWhere('content', 'like', '%məzuniyyət%')
                    ->orWhere('content', 'like', '%mezuniyyet%')
                    ->orWhere('content', 'like', '%vacation%');
            })
            ->orderBy('id')
            ->first();
    }

    private function resolveOrCreateVacationOperationalComponent(OrderType $orderType): Component
    {
        $usesOrderTypeColumn = Schema::hasColumn('components', 'order_type_id');
        $foreignColumn = $usesOrderTypeColumn ? 'order_type_id' : 'order_id';
        $foreignValue = $usesOrderTypeColumn ? $orderType->id : $orderType->order_id;

        $existing = Component::query()
            ->where($foreignColumn, $foreignValue)
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $payload = [
            $foreignColumn => $foreignValue,
            'rank_id' => null,
            'name' => $orderType->name ?: 'Məzuniyyət',
            'content' => 'Auto-generated self-service vacation component.',
            'dynamic_fields' => null,
        ];

        if (Schema::hasColumn('components', 'title')) {
            $payload['title'] = $orderType->name ?: 'Məzuniyyət';
        }

        $componentId = DB::table('components')->insertGetId($payload);

        return Component::query()->findOrFail($componentId);
    }

    private function applyPatch(object $requestable, array $patch): void
    {
        if ($patch === []) {
            return;
        }

        if ($requestable instanceof Leave) {
            $requestable->forceFill(array_intersect_key($patch, array_flip([
                'starts_at', 'ends_at', 'reason', 'duration_unit', 'partial_day_part', 'starts_time', 'ends_time',
            ])))->save();

            return;
        }

        if ($requestable instanceof PersonnelVacation) {
            $fillable = array_intersect_key($patch, array_flip(['vacation_places', 'start_date', 'end_date']));
            if (isset($fillable['start_date'], $fillable['end_date'])) {
                $end = \Carbon\Carbon::parse($fillable['end_date'])->startOfDay();
                $start = \Carbon\Carbon::parse($fillable['start_date'])->startOfDay();
                $fillable['duration'] = $start->diffInDays($end) + 1;
                $fillable['return_work_date'] = $end->copy()->addDay()->toDateString();
            }

            $requestable->forceFill($fillable)->save();

            return;
        }

        if ($requestable instanceof PersonnelBusinessTrip) {
            $requestable->forceFill(array_intersect_key($patch, array_flip([
                'location', 'description', 'start_date', 'end_date',
            ])))->save();
        }
    }

    private function requestableLabel(?string $type): string
    {
        return match ($type) {
            Leave::class => __('personnel::my_hr.requests.types.leave'),
            PersonnelVacation::class => __('personnel::my_hr.requests.types.vacation'),
            PersonnelBusinessTrip::class => __('personnel::my_hr.requests.types.business_trip'),
            default => __('personnel::my_hr.review.types.unknown'),
        };
    }

    private function notifyRequester(?User $user, string $subjectKey, array $payload): void
    {
        if (! $user) {
            return;
        }

        $user->notify(new PlatformNotification(
            'database',
            $payload,
            __('personnel::my_hr.review.notifications.subjects.'.$subjectKey),
            (string) ($payload['body'] ?? '')
        ));
    }

    private function canReviewAll(User $reviewer): bool
    {
        return $reviewer->can('review-all-self-service-requests')
            || $reviewer->can('review-self-service-requests');
    }

    private function reviewerPersonnelId(User $reviewer): ?int
    {
        if ($reviewer->relationLoaded('personnel')) {
            return $reviewer->personnel?->id ? (int) $reviewer->personnel->id : null;
        }

        $resolved = $this->userPersonnelLinkResolver->resolve($reviewer);

        return $resolved ? (int) $resolved : null;
    }
}
