<?php

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Modules\Personnel\Application\Services\MyHr\Review\SelfServiceReviewAuthorizationService;

require_once __DIR__.'/Support/identity_fixtures.php';

/*
 * `review-self-service-requests` yalnız təyin olunduğu müraciətlərə baxır; bütün
 * müraciətlərə baxış yalnız `review-all-self-service-requests` ilədir. Heç kim — hətta
 * «hamısına baxış» icazəsi olan da — öz müraciətini təsdiqləyə/rədd edə bilməz.
 */

beforeEach(function (): void {
    $this->authz = app(SelfServiceReviewAuthorizationService::class);
    $this->employee = identityPersonnel('SA-EMP');
    $this->reviewerCard = identityPersonnel('SA-REV');
});

function selfServiceLeave(string $tabelNo, ?int $assignedTo = null): Leave
{
    return (new Leave)->forceFill([
        'tabel_no' => $tabelNo,
        'status_id' => OrderStatusEnum::PENDING->value,
        'submission_source' => 'employee_self_service',
        'assigned_to' => $assignedTo,
    ]);
}

it('does not give review-self-service-requests a global reach', function (): void {
    $reviewer = identityUser(['review-self-service-requests']);
    identityLink($reviewer, $this->reviewerCard);

    expect($this->authz->canReviewAll($reviewer))->toBeFalse()
        ->and($this->authz->canReviewLeave(selfServiceLeave('SA-EMP'), $reviewer))->toBeFalse()
        ->and($this->authz->canReviewLeave(selfServiceLeave('SA-EMP', $this->reviewerCard->id), $reviewer))->toBeTrue();
});

it('lets review-all reviewers decide other people\'s requests but never their own', function (): void {
    $hr = identityUser(['review-all-self-service-requests']);
    identityLink($hr, $this->reviewerCard);

    $ownVacation = (new PersonnelVacation)->forceFill(['tabel_no' => 'SA-REV', 'submission_source' => 'employee_self_service', 'approval_status' => 'pending']);
    $ownTrip = (new PersonnelBusinessTrip)->forceFill(['tabel_no' => 'SA-REV', 'submission_source' => 'employee_self_service', 'approval_status' => 'pending']);

    expect($this->authz->canReviewLeave(selfServiceLeave('SA-EMP'), $hr))->toBeTrue()
        ->and($this->authz->canReviewLeave(selfServiceLeave('SA-REV'), $hr))->toBeFalse()
        ->and($this->authz->canReviewVacation($ownVacation, $hr))->toBeFalse()
        ->and($this->authz->canReviewBusinessTrip($ownTrip, $hr))->toBeFalse();
});

it('refuses self-approval even when the route assigns the request to its own author', function (): void {
    $manager = identityUser(['review-self-service-requests']);
    identityLink($manager, $this->reviewerCard);

    expect($this->authz->canReviewLeave(selfServiceLeave('SA-REV', $this->reviewerCard->id), $manager))->toBeFalse();
});
