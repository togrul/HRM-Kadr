<?php

use App\Models\Role;
use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    private array $adminRoles = [
        'Admin',
        'HR Admin',
        'HR Manager',
        'HR Employee',
    ];

    private string $employeeRole = 'Employee Self-Service';

    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $now = Carbon::now();

        $managePermission = Permission::query()->updateOrCreate(
            ['name' => 'manage-my-hr-accounts', 'guard_name' => 'web'],
            [
                'description' => PermissionDescriptionCatalog::describe('manage-my-hr-accounts'),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $showPermission = Permission::query()->updateOrCreate(
            ['name' => 'show-my-hr', 'guard_name' => 'web'],
            [
                'description' => PermissionDescriptionCatalog::describe('show-my-hr'),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $this->assignPermissionsToAdminRoles([$managePermission->id]);

        $employeeRole = Role::query()->firstOrCreate(
            ['name' => $this->employeeRole, 'guard_name' => 'web'],
            ['created_at' => $now, 'updated_at' => $now]
        );

        DB::table('role_has_permissions')->upsert([
            ['role_id' => $employeeRole->id, 'permission_id' => $showPermission->id],
        ], ['permission_id', 'role_id'], []);
    }

    public function down(): void
    {
        $permissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['manage-my-hr-accounts'])
            ->pluck('id')
            ->all();

        if (! empty($permissionIds)) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        Permission::query()->where('guard_name', 'web')->where('name', 'manage-my-hr-accounts')->delete();
    }

    private function assignPermissionsToAdminRoles(array $permissionIds): void
    {
        $roleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->adminRoles)
            ->pluck('id')
            ->all();

        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $rows[] = ['permission_id' => $permissionId, 'role_id' => $roleId];
            }
        }

        if (! empty($rows)) {
            DB::table('role_has_permissions')->upsert($rows, ['permission_id', 'role_id'], []);
        }
    }
};
