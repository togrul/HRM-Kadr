<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * `access-settings` əvvəllər tam admin hüququ demək idi: istifadəçi hesablarını, rolları və
 * icazələri də idarə edirdi. İndi bu iki iş ayrı icazədir:
 *  - `manage-users` — istifadəçi hesabları və istifadəçi ↔ əməkdaş bağları;
 *  - `manage-roles` — rollar, icazələr və rolların struktur girişi.
 * Mövcud qurulumda heç kim hüququnu itirməsin deyə hər ikisi bu gün `access-settings`
 * daşıyan rollara verilir; sonra admin onları daraltmaq üçün ayrı-ayrı geri ala bilər.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = ['manage-users', 'manage-roles'];

    public function up(): void
    {
        $now = now();
        $hasDescription = Schema::hasColumn('permissions', 'description');
        $updateColumns = $hasDescription ? ['updated_at', 'description'] : ['updated_at'];

        DB::table('permissions')->upsert(array_map(function (string $name) use ($now, $hasDescription): array {
            $row = ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now];

            if ($hasDescription) {
                $row['description'] = PermissionDescriptionCatalog::describe($name);
            }

            return $row;
        }, $this->permissions), ['name', 'guard_name'], $updateColumns);

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('id')
            ->all();

        $roleIds = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.guard_name', 'web')
            ->where('permissions.name', 'access-settings')
            ->pluck('role_has_permissions.role_id')
            ->merge(DB::table('roles')->where('guard_name', 'web')->where('name', 'Admin')->pluck('id'))
            ->unique()
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
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
