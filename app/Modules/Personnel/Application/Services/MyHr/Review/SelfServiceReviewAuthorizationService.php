<?php

namespace App\Modules\Personnel\Application\Services\MyHr\Review;

use App\Models\EmployeeRequestChangeRequest;
use App\Models\Leave;
use App\Models\Personnel;
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

        if ($this->isOwnRequest((string) $leave->tabel_no, $reviewer)) {
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

        if ($this->isOwnRequest((string) $vacation->tabel_no, $reviewer)) {
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

        if ($this->isOwnRequest((string) $trip->tabel_no, $reviewer)) {
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

        $change->loadMissing('requestable');
        $requestable = $change->requestable;

        if (! $requestable || $this->isOwnRequest((string) $requestable->getAttribute('tabel_no'), $reviewer)) {
            return false;
        }

        if ($this->canReviewAll($reviewer)) {
            return true;
        }

        $reviewerPersonnelId = $this->reviewerPersonnelId($reviewer);
        if (! $reviewerPersonnelId) {
            return false;
        }

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

    /**
     * Bütün müraciətlərə baxış yalnız `review-all-self-service-requests` ilə verilir;
     * `review-self-service-requests` yalnız təyin olunduğu (və ya ehtiyat təsdiqçi olduğu)
     * müraciətlərə baxmağa imkan verir.
     */
    public function canReviewAll(User $reviewer): bool
    {
        return app(HrPolicyPackService::class)->permissionEnabled('self_service_reviews.review_all')
            && $reviewer->can('review-all-self-service-requests');
    }

    /**
     * Rəyçinin əməkdaş kartı — yalnız açıq bağ (user_personnel_links) üzrə.
     */
    public function reviewerPersonnelId(User $reviewer): ?int
    {
        return $this->userPersonnelLinkResolver->resolve($reviewer);
    }

    /**
     * Öz müraciətini təsdiqləmək/rədd etmək qadağandır — «hamısına baxış» icazəsi olsa belə.
     * Müraciət sahibini tabel nömrəsi müəyyən edir; rəyçinin bağlı kartı ilə müqayisə olunur.
     */
    public function isOwnRequest(string $requestTabelNo, User $reviewer): bool
    {
        $reviewerPersonnelId = $this->reviewerPersonnelId($reviewer);

        if (! $reviewerPersonnelId || trim($requestTabelNo) === '') {
            return false;
        }

        $reviewerTabelNo = Personnel::query()->whereKey($reviewerPersonnelId)->value('tabel_no');

        return $reviewerTabelNo !== null && trim((string) $reviewerTabelNo) === trim($requestTabelNo);
    }
}
