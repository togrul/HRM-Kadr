<?php

namespace Tests\Feature\Authorization;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\Leave;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\StaffSchedule;
use App\Models\User;
use App\Modules\BusinessTrips\Policies\BusinessTripPolicy;
use App\Modules\Candidates\Policies\CandidateApplicationPolicy;
use App\Modules\Candidates\Policies\CandidatePolicy;
use App\Modules\Leaves\Policies\LeavePolicy;
use App\Modules\Orders\Policies\OrderLogPolicy;
use App\Modules\Orders\Policies\OrderPolicy;
use App\Modules\Personnel\Policies\PersonnelPolicy;
use App\Modules\Staff\Policies\StaffSchedulePolicy;
use App\Modules\Vacation\Policies\VacationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pins the authorization matrix for every model policy: each ability must allow a
 * user that holds the required permission and deny one that does not. Guards against
 * accidental permission renames / dropped checks.
 */
class PolicyAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** Methods that take a model as their second argument. */
    private const MODEL_METHODS = ['view', 'update', 'delete', 'restore', 'forceDelete', 'transition', 'reject', 'appoint'];

    public static function policyMatrix(): array
    {
        // [policy, method, requiredPermission, modelClass|null]
        return [
            // PersonnelPolicy
            [PersonnelPolicy::class, 'viewAny', 'show-personnels', null],
            [PersonnelPolicy::class, 'view', 'show-personnels', Personnel::class],
            [PersonnelPolicy::class, 'create', 'add-personnels', null],
            [PersonnelPolicy::class, 'update', 'edit-personnels', Personnel::class],
            [PersonnelPolicy::class, 'delete', 'delete-personnels', Personnel::class],
            [PersonnelPolicy::class, 'forceDelete', 'delete-personnels', Personnel::class],
            [PersonnelPolicy::class, 'export', 'show-personnels', null],

            // OrderPolicy
            [OrderPolicy::class, 'viewAny', 'show-orders', null],
            [OrderPolicy::class, 'create', 'add-orders', null],
            [OrderPolicy::class, 'update', 'edit-orders', Order::class],
            [OrderPolicy::class, 'delete', 'delete-orders', Order::class],
            [OrderPolicy::class, 'export', 'export-orders', null],

            // OrderLogPolicy
            [OrderLogPolicy::class, 'viewAny', 'show-orders', null],
            [OrderLogPolicy::class, 'update', 'edit-orders', OrderLog::class],
            [OrderLogPolicy::class, 'delete', 'delete-orders', OrderLog::class],

            // LeavePolicy
            [LeavePolicy::class, 'viewAny', 'show-leaves', null],
            [LeavePolicy::class, 'create', 'add-leaves', null],
            [LeavePolicy::class, 'update', 'edit-leaves', Leave::class],
            [LeavePolicy::class, 'delete', 'delete-leaves', Leave::class],
            [LeavePolicy::class, 'export', 'export-leaves', null],

            // CandidatePolicy
            [CandidatePolicy::class, 'viewAny', 'show-candidates', null],
            [CandidatePolicy::class, 'create', 'add-candidates', null],
            [CandidatePolicy::class, 'update', 'edit-candidates', Candidate::class],
            [CandidatePolicy::class, 'delete', 'delete-candidates', Candidate::class],
            [CandidatePolicy::class, 'export', 'export-candidates', null],

            // CandidateApplicationPolicy (OR permissions — primary used here)
            [CandidateApplicationPolicy::class, 'viewAny', 'show-candidates', null],
            [CandidateApplicationPolicy::class, 'create', 'candidate-applications.create', null],
            [CandidateApplicationPolicy::class, 'transition', 'candidate-applications.transition', CandidateApplication::class],
            [CandidateApplicationPolicy::class, 'reject', 'candidate-applications.reject', CandidateApplication::class],
            [CandidateApplicationPolicy::class, 'appoint', 'candidate-applications.appoint', CandidateApplication::class],

            // BusinessTripPolicy
            [BusinessTripPolicy::class, 'viewAny', 'show-business_trips', null],
            [BusinessTripPolicy::class, 'create', 'add-business_trips', null],
            [BusinessTripPolicy::class, 'update', 'edit-business_trips', PersonnelBusinessTrip::class],
            [BusinessTripPolicy::class, 'delete', 'delete-business_trips', PersonnelBusinessTrip::class],

            // StaffSchedulePolicy
            [StaffSchedulePolicy::class, 'viewAny', 'show-staff', null],
            [StaffSchedulePolicy::class, 'create', 'add-staff', null],
            [StaffSchedulePolicy::class, 'update', 'edit-staff', StaffSchedule::class],
            [StaffSchedulePolicy::class, 'delete', 'delete-staff', StaffSchedule::class],

            // VacationPolicy
            [VacationPolicy::class, 'viewAny', 'show-vacations', null],
            [VacationPolicy::class, 'create', 'add-vacations', null],
            [VacationPolicy::class, 'update', 'edit-vacations', PersonnelVacation::class],
            [VacationPolicy::class, 'delete', 'delete-vacations', PersonnelVacation::class],
        ];
    }

    /**
     * @dataProvider policyMatrix
     */
    public function test_policy_ability_respects_required_permission(
        string $policyClass,
        string $method,
        string $permission,
        ?string $modelClass
    ): void {
        Permission::findOrCreate($permission, 'web');

        $policy = new $policyClass();
        $args = in_array($method, self::MODEL_METHODS, true) && $modelClass !== null
            ? [new $modelClass()]
            : [];

        $granted = User::factory()->create();
        $granted->givePermissionTo($permission);
        $this->assertTrue(
            $policy->{$method}($granted->fresh(), ...$args),
            "{$policyClass}::{$method}() must ALLOW a user holding '{$permission}'."
        );

        $denied = User::factory()->create();
        $this->assertFalse(
            $policy->{$method}($denied->fresh(), ...$args),
            "{$policyClass}::{$method}() must DENY a user without '{$permission}'."
        );
    }
}
