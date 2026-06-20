<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string,array<int,string>>
     */
    private array $rolePermissionMap = [
        'HR Admin' => [
            'manage-attendance-settings',
            'manage-attendance-shifts',
        ],
    ];

    public function up(): void
    {
        $now = Carbon::now();

        foreach ($this->rolePermissionMap as $roleName => $permissions) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $roleId) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->pluck('id')
                ->all();

            if ($permissionIds === []) {
                continue;
            }

            $rows = array_map(
                fn (int $permissionId): array => [
                    'permission_id' => $permissionId,
                    'role_id' => (int) $roleId,
                ],
                $permissionIds
            );

            DB::table('role_has_permissions')->upsert($rows, ['permission_id', 'role_id'], []);

            DB::table('roles')
                ->where('id', $roleId)
                ->update(['updated_at' => $now]);
        }
    }

    public function down(): void
    {
        foreach ($this->rolePermissionMap as $roleName => $permissions) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $roleId) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissions)
                ->where('guard_name', 'web')
                ->pluck('id')
                ->all();

            if ($permissionIds === []) {
                continue;
            }

            DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};
