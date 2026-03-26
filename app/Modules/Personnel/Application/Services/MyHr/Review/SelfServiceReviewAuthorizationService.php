<?php

namespace App\Modules\Personnel\Application\Services\MyHr\Review;

use App\Models\EmployeeRequestChangeRequest;
use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Services\HrPolicies\HrPolicyPackService;
use App\Services\UserPersonnelLinkResolver;

class SelfServiceReviewAuthorizationService
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

    public function canReviewAll(User $reviewer): bool
    {
        return app(HrPolicyPackService::class)->permissionEnabled('self_service_reviews.review_all')
            && ($reviewer->can('review-all-self-service-requests')
            || $reviewer->can('review-self-service-requests'));
    }

    public function reviewerPersonnelId(User $reviewer): ?int
    {
        if ($reviewer->relationLoaded('personnel')) {
            return $reviewer->personnel?->id ? (int) $reviewer->personnel->id : null;
        }

        $resolved = $this->userPersonnelLinkResolver->resolve($reviewer);

        return $resolved ? (int) $resolved : null;
    }
}
