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
        'attendance.overtime.approve' => [
            'approve-attendance-overtime',
            'manage-attendance',
            'confirmation-general',
            'access-admin',
        ],
        'attendance.exceptions.resolve' => [
            'edit-attendance-exceptions',
            'manage-attendance',
            'manage-staff',
            'access-admin',
        ],
        'attendance.month.manage' => [
            'manage-attendance-month-close',
            'manage-attendance',
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

