<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'show-employee-lifecycle',
        'manage-employee-lifecycle',
    ];

    private array $roles = [
        'Admin',
        'HR Admin',
    ];

    public function up(): void
    {
        $now = now();
        $hasDescriptionColumn = Schema::hasColumn('permissions', 'description');

        $rows = collect($this->permissions)
            ->map(function (string $name) use ($hasDescriptionColumn, $now): array {
                $row = [
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasDescriptionColumn) {
                    $row['description'] = PermissionDescriptionCatalog::describe($name);
                }

                return $row;
            })
            ->all();

        $updateColumns = ['updated_at'];

        if ($hasDescriptionColumn) {
            $updateColumns[] = 'description';
        }

        DB::table('permissions')->upsert($rows, ['name', 'guard_name'], $updateColumns);

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->roles)
            ->pluck('id');

        if ($permissionIds->isNotEmpty() && $roleIds->isNotEmpty()) {
            DB::table('role_has_permissions')->insertOrIgnore(
                $roleIds
                    ->flatMap(fn ($roleId) => $permissionIds->map(fn ($permissionId): array => [
                        'role_id' => (int) $roleId,
                        'permission_id' => (int) $permissionId,
                    ]))
                    ->values()
                    ->all()
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->where('name', 'manage-employee-lifecycle')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            DB::table('permissions')
                ->whereIn('id', $permissionIds)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
