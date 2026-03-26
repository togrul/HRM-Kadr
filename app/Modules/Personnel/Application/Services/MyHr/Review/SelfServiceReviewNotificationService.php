<?php

namespace App\Modules\Personnel\Application\Services\MyHr\Review;

use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Notifications\PlatformNotification;

class SelfServiceReviewNotificationService
{
    public function notifyRequestReviewed(?User $user, string $requestTypeLabel, bool $approved, ?string $note = null): void
    {
        $this->notify(
            $user,
            'self_service_request_reviewed',
            [
                'action' => 'selfServiceRequestReviewed',
                'category' => __('personnel::my_hr.review.notifications.category'),
                'message' => $approved
                    ? __('personnel::my_hr.review.notifications.approved')
                    : __('personnel::my_hr.review.notifications.rejected'),
                'name' => $requestTypeLabel,
                'body' => $note ?: ($approved
                    ? __('personnel::my_hr.review.notifications.default_approved_body')
                    : __('personnel::my_hr.review.notifications.default_rejected_body')),
            ]
        );
    }

    public function notifyCorrectionReviewed(?User $user, ?string $requestableType, bool $approved, ?string $note = null): void
    {
        $this->notify(
            $user,
            'self_service_correction_reviewed',
            [
                'action' => 'selfServiceCorrectionReviewed',
                'category' => __('personnel::my_hr.review.notifications.category'),
                'message' => $approved
                    ? __('personnel::my_hr.review.notifications.correction_approved')
                    : __('personnel::my_hr.review.notifications.correction_rejected'),
                'name' => $this->requestableLabel($requestableType),
                'body' => $note ?: ($approved
                    ? __('personnel::my_hr.review.notifications.default_correction_approved_body')
                    : __('personnel::my_hr.review.notifications.default_correction_rejected_body')),
            ]
        );
    }

    private function notify(?User $user, string $subjectKey, array $payload): void
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

    private function requestableLabel(?string $type): string
    {
        return match ($type) {
            Leave::class => __('personnel::my_hr.requests.types.leave'),
            PersonnelVacation::class => __('personnel::my_hr.requests.types.vacation'),
            PersonnelBusinessTrip::class => __('personnel::my_hr.requests.types.business_trip'),
            default => __('personnel::my_hr.review.types.unknown'),
        };
    }
}
