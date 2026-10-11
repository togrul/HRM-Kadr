<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Əməkdaş 360 zaman xəttinin ixracı (PDF/XLSX) və həssas hadisələrin (intizam, tibbi)
 * redaksiyasız görünməsi üçün icazələr. Mövcud qurğularda yalnız Admin və HR Admin-ə verilir.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = ['export-personnel-360', 'view-personnel-360-sensitive'];

    /** @var list<string> */
    private array $roles = ['Admin', 'HR Admin'];

    public function up(): void
    {
        $now = now();
        $hasDescription = Schema::hasColumn('permissions', 'description');
        $updateColumns = $hasDescription ? ['updated_at', 'description'] : ['updated_at'];

        DB::table('permissions')->upsert(array_map(fn (string $permission): array => [
            'name' => $permission,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
            ...($hasDescription ? ['description' => PermissionDescriptionCatalog::describe($permission)] : []),
        ], $this->permissions), ['name', 'guard_name'], $updateColumns);

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('id')
            ->all();

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->roles)
            ->pluck('id')
            ->all();

        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $rows[] = ['role_id' => (int) $roleId, 'permission_id' => (int) $permissionId];
            }
        }

        if ($rows !== []) {
            DB::table('role_has_permissions')->insertOrIgnore($rows);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('id')
            ->all();

        if ($ids !== []) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
