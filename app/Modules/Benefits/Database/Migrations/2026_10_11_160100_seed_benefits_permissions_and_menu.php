<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * İmtiyazlar modulunun icazələri və menyu sətri. Mövcud qurğularda icazələr yalnız Admin və
 * HR Admin rollarına verilir; işçinin öz seçimi (Şəxsi kabinet) ayrıca icazə tələb etmir.
 * insertOrIgnore: MySQL-də upsert(..., []) sadə insert-ə düşür, təkrar işlədilmədə dublikat xətası verir.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = ['show-benefits', 'manage-benefits', 'show-benefit-costs'];

    /** @var list<string> */
    private array $roles = ['Admin', 'HR Admin'];

    public function up(): void
    {
        $now = now();
        $hasDescription = Schema::hasColumn('permissions', 'description');

        DB::table('permissions')->insertOrIgnore(array_map(fn (string $permission): array => [
            'name' => $permission,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
            ...($hasDescription ? ['description' => PermissionDescriptionCatalog::describe($permission)] : []),
        ], $this->permissions));

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('id', 'name')
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

        if (Schema::hasTable('menus') && ! DB::table('menus')->where('url', 'benefits')->exists()) {
            DB::table('menus')->insert([
                'name' => 'ui::menu.items.benefits',
                'icon' => 'benefits-icon',
                'color' => 'zinc',
                'order' => 19,
                'is_active' => 1,
                'url' => 'benefits',
                ...(Schema::hasColumn('menus', 'permission_id') ? ['permission_id' => $permissionIds['show-benefits'] ?? null] : []),
                ...(Schema::hasColumn('menus', 'created_at') ? ['created_at' => $now, 'updated_at' => $now] : []),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('url', 'benefits')->delete();
        }

        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('id')
            ->all();

        if ($ids !== []) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
