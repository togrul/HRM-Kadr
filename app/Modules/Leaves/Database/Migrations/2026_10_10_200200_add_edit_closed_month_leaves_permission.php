<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * "edit-closed-month-leaves": əmək haqqı üçün bağlanmış aya düşən icazəni yaratmaq,
 * dəyişmək və ya silmək. Bağlı ayın davamiyyəti və əmək haqqı artıq hesablanıb, ona görə
 * bu, adi redaktədən ayrı icazədir. Mövcud qurğularda Admin rolu alır.
 */
return new class extends Migration
{
    private string $permission = 'edit-closed-month-leaves';

    /** @var list<string> */
    private array $roles = ['Admin'];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

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
