<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * İşçi münasibətləri icazələri və menyu bəndi.
 *
 * Rolların paylanması: Admin bütün icazələri alır (anonim şikayətçinin kimliyini açmaq
 * daxil); HR Admin kimlik açma istisna olmaqla hamısını; özünəxidmət rolu yalnız öz
 * müraciətini göndərmək icazəsini. Rəhbərlərə (`create-` + `show-`) rol idarəsindən verilir.
 * Bütün yazılar insertOrIgnore ilə — MySQL-də təkrar işə salınma dublikat yaratmır.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $grants = [
        'Admin' => [
            'show-employee-relations',
            'create-employee-relations',
            'manage-employee-relations',
            'reveal-employee-relations-reporter',
            'show-employee-relations-statistics',
            'submit-own-employee-relations',
        ],
        'HR Admin' => [
            'show-employee-relations',
            'create-employee-relations',
            'manage-employee-relations',
            'show-employee-relations-statistics',
        ],
        'Employee Self-Service' => [
            'submit-own-employee-relations',
        ],
    ];

    /** @var list<string> */
    private array $permissions = [
        'show-employee-relations',
        'create-employee-relations',
        'manage-employee-relations',
        'reveal-employee-relations-reporter',
        'show-employee-relations-statistics',
        'submit-own-employee-relations',
    ];

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
            ->pluck('id', 'name');

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys($this->grants))
            ->pluck('id', 'name');

        $rows = [];
        foreach ($this->grants as $role => $permissions) {
            if (! isset($roleIds[$role])) {
                continue;
            }

            foreach ($permissions as $permission) {
                if (isset($permissionIds[$permission])) {
                    $rows[] = ['role_id' => (int) $roleIds[$role], 'permission_id' => (int) $permissionIds[$permission]];
                }
            }
        }

        if ($rows !== []) {
            DB::table('role_has_permissions')->insertOrIgnore($rows);
        }

        if (Schema::hasTable('menus') && isset($permissionIds['show-employee-relations'])) {
            $exists = DB::table('menus')->where('url', 'employee-relations')->exists();

            if (! $exists) {
                DB::table('menus')->insert([
                    'name' => 'ui::menu.items.employee_relations',
                    'icon' => 'shield-user-icon',
                    'color' => 'zinc',
                    'order' => 18,
                    'is_active' => 1,
                    'url' => 'employee-relations',
                    'permission_id' => (int) $permissionIds['show-employee-relations'],
                    ...(Schema::hasColumn('menus', 'created_at') ? ['created_at' => $now, 'updated_at' => $now] : []),
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('url', 'employee-relations')->delete();
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
