<?php

namespace App\Modules\Personnel\Contracts;

use App\Models\EmployeeRequestChangeRequest;
use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;

/**
 * Sanctioned cross-module surface for reviewing (approving/rejecting) employee
 * self-service requests — leaves, vacations, business trips and correction
 * requests. Other modules depend on THIS interface — never on the concrete
 * Personnel\...\MyHr implementation — so the Personnel module can evolve its
 * internals without breaking consumers.
 *
 * @see \App\Modules\Personnel\Application\Services\MyHr\MyHrRequestReviewService
 */
interface MyHrRequestReview
{
    public function canReviewLeave(Leave $leave, User $reviewer): bool;

    public function canReviewVacation(PersonnelVacation $vacation, User $reviewer): bool;

    public function canReviewBusinessTrip(PersonnelBusinessTrip $trip, User $reviewer): bool;

    public function canReviewCorrection(EmployeeRequestChangeRequest $change, User $reviewer): bool;

    public function approveLeave(Leave $leave, User $reviewer, ?string $note = null): void;

    public function rejectLeave(Leave $leave, User $reviewer, ?string $note = null): void;

    public function approveVacation(PersonnelVacation $vacation, User $reviewer, ?string $note = null): void;

    public function rejectVacation(PersonnelVacation $vacation, User $reviewer, ?string $note = null): void;

    public function approveBusinessTrip(PersonnelBusinessTrip $trip, User $reviewer, ?string $note = null): void;

    public function rejectBusinessTrip(PersonnelBusinessTrip $trip, User $reviewer, ?string $note = null): void;

    public function bindOperationalVacationOrder(PersonnelVacation $vacation, User $reviewer): void;

    public function approveCorrection(EmployeeRequestChangeRequest $change, User $reviewer, ?string $note = null): void;

    public function rejectCorrection(EmployeeRequestChangeRequest $change, User $reviewer, ?string $note = null): void;
}
