<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * `approve-leaves`: who may record a leave straight as approved instead of sending it
 * through its approval route. Idempotent; granted to the Admin role, which already holds
 * every leave permission — everyone else has to be given it explicitly.
 */
return new class extends Migration
{
    private const PERMISSION = 'approve-leaves';

    public function up(): void
    {
        $permission = Permission::query()->firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);

        if (DB::getSchemaBuilder()->hasColumn('permissions', 'description')) {
            $permission->forceFill(['description' => PermissionDescriptionCatalog::describe(self::PERMISSION)])->save();
        }

        Role::query()->where('guard_name', 'web')->where('name', 'Admin')->first()?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('guard_name', 'web')->where('name', self::PERMISSION)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
