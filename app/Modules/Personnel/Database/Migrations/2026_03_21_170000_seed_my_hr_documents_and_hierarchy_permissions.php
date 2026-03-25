<?php

use App\Models\Role;
use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $now = Carbon::now();
        $permissions = [
            'view-own-personnel-documents',
            'view-own-hierarchy',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                [
                    'description' => PermissionDescriptionCatalog::describe($permission),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $employeeRole = Role::query()->where('guard_name', 'web')->where('name', 'Employee Self-Service')->first();

        if ($employeeRole) {
            $rows = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissions)
                ->pluck('id')
                ->map(fn (int $permissionId): array => [
                    'role_id' => $employeeRole->id,
                    'permission_id' => $permissionId,
                ])
                ->all();

            if ($rows !== []) {
                DB::table('role_has_permissions')->upsert($rows, ['role_id', 'permission_id'], []);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['view-own-personnel-documents', 'view-own-hierarchy'])
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            Permission::query()->whereIn('id', $permissionIds)->delete();
        }
    }
};
