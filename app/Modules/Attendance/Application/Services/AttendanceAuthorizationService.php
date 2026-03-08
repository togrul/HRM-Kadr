<?php

namespace App\Modules\Attendance\Application\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class AttendanceAuthorizationService
{
    /**
     * @var array<string,array<int,string>>
     */
    private const MATRIX = [
        'attendance.view' => [
            'show-attendance',
            'manage-attendance',
            'show-staff',
            'manage-staff',
            'access-admin',
        ],
        'attendance.daily.view' => [
            'show-attendance-daily-monitor',
            'show-attendance',
            'manage-attendance',
            'show-staff',
            'manage-staff',
            'access-admin',
        ],
        'attendance.puantaj.view' => [
            'show-attendance-puantaj',
            'show-attendance',
            'manage-attendance',
            'show-staff',
            'manage-staff',
            'access-admin',
        ],
        'attendance.manual.view' => [
            'show-attendance-manual',
            'add-attendance-manual',
            'edit-attendance-manual',
            'approve-attendance-manual',
            'show-attendance',
            'manage-attendance',
            'manage-staff',
            'access-admin',
        ],
        'attendance.manual.write' => [
            'add-attendance-manual',
            'edit-attendance-manual',
            'manage-attendance',
            'manage-staff',
            'access-admin',
        ],
        'attendance.manual.approve' => [
            'approve-attendance-manual',
            'manage-attendance',
            'confirmation-general',
            'access-admin',
        ],
        'attendance.overtime.view' => [
            'show-attendance-overtime',
            'approve-attendance-overtime',
            'show-attendance',
            'manage-attendance',
            'access-admin',
        ],
        'attendance.overtime.approve' => [
            'approve-attendance-overtime',
            'manage-attendance',
            'confirmation-general',
            'access-admin',
        ],
        'attendance.exceptions.view' => [
            'show-attendance-exceptions',
            'edit-attendance-exceptions',
            'show-attendance',
            'manage-attendance',
            'manage-staff',
            'access-admin',
        ],
        'attendance.exceptions.resolve' => [
            'edit-attendance-exceptions',
            'manage-attendance',
            'manage-staff',
            'access-admin',
        ],
        'attendance.month.view' => [
            'show-attendance-month-close',
            'manage-attendance-month-close',
            'export-attendance',
            'manage-attendance',
            'access-admin',
        ],
        'attendance.month.manage' => [
            'manage-attendance-month-close',
            'manage-attendance',
            'access-admin',
        ],
        'attendance.settings.manage' => [
            'manage-attendance-settings',
            'access-admin',
        ],
        'attendance.shifts.manage' => [
            'manage-attendance-shifts',
            'manage-attendance-settings',
            'access-admin',
        ],
        'attendance.export' => [
            'export-attendance',
            'manage-attendance',
            'export-staff',
            'access-admin',
        ],
    ];

    public function can(string $scope, ?Authenticatable $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (! $user) {
            return false;
        }

        $permissions = self::MATRIX[$scope] ?? [];
        foreach ($permissions as $permission) {
            if (method_exists($user, 'can') && $user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(string $scope, ?Authenticatable $user = null): void
    {
        if (! $this->can($scope, $user)) {
            throw new AuthorizationException('You do not have permission to perform this attendance action.');
        }
    }
}
