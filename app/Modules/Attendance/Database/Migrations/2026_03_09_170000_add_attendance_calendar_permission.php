<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<int,string>
     */
    private array $permissions = [
        'manage-attendance-calendars',
    ];

    public function up(): void
    {
        $now = now();

        $rows = array_map(
            fn (string $name): array => [
                'name' => $name,
                'description' => PermissionDescriptionCatalog::describe($name),
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $this->permissions
        );

        DB::table('permissions')->upsert($rows, ['name', 'guard_name'], ['description', 'updated_at']);

        $adminRoleId = DB::table('roles')
            ->where('name', 'Admin')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $adminRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        $pivotRows = $permissionIds
            ->map(fn ($permissionId): array => [
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ])
            ->all();

        if ($pivotRows !== []) {
            DB::table('role_has_permissions')->upsert($pivotRows, ['permission_id', 'role_id'], []);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->delete();
    }
};
