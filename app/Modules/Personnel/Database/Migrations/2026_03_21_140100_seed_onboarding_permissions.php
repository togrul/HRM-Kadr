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

        $permissionNames = [
            'view-own-onboarding-documents',
            'acknowledge-own-onboarding-documents',
            'manage-onboarding-document-templates',
            'assign-onboarding-documents',
        ];

        $permissionIds = [];

        foreach ($permissionNames as $name) {
            $permission = Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'description' => PermissionDescriptionCatalog::describe($name),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $permissionIds[$name] = $permission->id;
        }

        $employeeRole = Role::query()->firstOrCreate(
            ['name' => $this->employeeRole, 'guard_name' => 'web'],
            ['created_at' => $now, 'updated_at' => $now]
        );

        DB::table('role_has_permissions')->upsert([
            ['role_id' => $employeeRole->id, 'permission_id' => $permissionIds['view-own-onboarding-documents']],
            ['role_id' => $employeeRole->id, 'permission_id' => $permissionIds['acknowledge-own-onboarding-documents']],
        ], ['permission_id', 'role_id'], []);

        $this->assignPermissionsToAdminRoles([
            $permissionIds['manage-onboarding-document-templates'],
            $permissionIds['assign-onboarding-documents'],
        ]);
    }

    public function down(): void
    {
        $permissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'view-own-onboarding-documents',
                'acknowledge-own-onboarding-documents',
                'manage-onboarding-document-templates',
                'assign-onboarding-documents',
            ])
            ->pluck('id')
            ->all();

        if (! empty($permissionIds)) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'view-own-onboarding-documents',
                'acknowledge-own-onboarding-documents',
                'manage-onboarding-document-templates',
                'assign-onboarding-documents',
            ])
            ->delete();
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
