<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * "revert-orders": taking an order out of the approved state (revert to pending, or
 * cancel) undoes its HR effect after the fact, so it is a permission of its own instead
 * of add-orders. Existing installs grant it to Admin and HR Admin.
 */
return new class extends Migration
{
    private string $permission = 'revert-orders';

    /** @var list<string> */
    private array $roles = ['Admin', 'HR Admin'];

    public function up(): void
    {
        $now = now();
        $row = [
            'name' => $this->permission,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $updateColumns = ['updated_at'];

        if (Schema::hasColumn('permissions', 'description')) {
            $row['description'] = PermissionDescriptionCatalog::describe($this->permission);
            $updateColumns[] = 'description';
        }

        DB::table('permissions')->upsert([$row], ['name', 'guard_name'], $updateColumns);

        $permissionId = DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->value('id');

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->roles)
            ->pluck('id')
            ->all();

        if ($permissionId !== null && $roleIds !== []) {
            DB::table('role_has_permissions')->insertOrIgnore(array_map(
                fn (int|string $roleId): array => ['role_id' => (int) $roleId, 'permission_id' => (int) $permissionId],
                $roleIds,
            ));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->where('name', $this->permission)
            ->pluck('id')
            ->all();

        if ($ids !== []) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
