<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Remove the retired order-template-designer permissions. The old template
 * designer is gone; the new block designer is gated by the existing
 * edit-orders permission, so these are dead. Pivot rows are cleared first so
 * the delete is safe regardless of foreign-key cascade configuration.
 */
return new class extends Migration
{
    private array $permissions = [
        'manage-order-template-sets',
        'manage-order-template-metadata',
        'manage-order-template-versions',
    ];

    public function up(): void
    {
        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Irreversible: the order-template-designer permissions are retired.
    }
};
