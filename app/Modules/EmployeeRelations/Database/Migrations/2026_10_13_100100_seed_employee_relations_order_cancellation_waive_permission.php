<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Apellyasiya ilə ləğv olunmuş tənbehin əmri qüvvədə qalarkən işi bağlamaq üçün istisna
 * təsdiqi icazəsi. Kimlik açma icazəsi kimi yalnız Admin rolu alır: işə baxan HR Admin
 * özü həm yekun qərar verib, həm də nəzarəti yan keçə bilməsin. Başqa rollara rol
 * idarəsindən verilir. insertOrIgnore — təkrar işə salınma dublikat yaratmır.
 */
return new class extends Migration
{
    private const PERMISSION = 'waive-employee-relations-order-cancellation';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->insertOrIgnore([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
            ...(Schema::hasColumn('permissions', 'description') ? ['description' => PermissionDescriptionCatalog::describe(self::PERMISSION)] : []),
        ]);

        $permissionId = DB::table('permissions')->where('guard_name', 'web')->where('name', self::PERMISSION)->value('id');
        $adminId = DB::table('roles')->where('guard_name', 'web')->where('name', 'Admin')->value('id');

        if ($permissionId !== null && $adminId !== null) {
            DB::table('role_has_permissions')->insertOrIgnore([['role_id' => (int) $adminId, 'permission_id' => (int) $permissionId]]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('guard_name', 'web')->where('name', self::PERMISSION)->value('id');

        if ($id !== null) {
            DB::table('role_has_permissions')->where('permission_id', $id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
