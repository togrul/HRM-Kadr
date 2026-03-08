<?php

use App\Models\Role;
use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * @var array<int,string>
     */
    private array $permissions = [
        'show-attendance',
        'manage-attendance',
        'add-attendance-manual',
        'edit-attendance-manual',
        'approve-attendance-manual',
        'approve-attendance-overtime',
        'manage-attendance-month-close',
        'edit-attendance-exceptions',
        'export-attendance',
    ];

    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $now = Carbon::now();
        $rows = array_map(
            fn (string $name) => [
                'name' => $name,
                'description' => PermissionDescriptionCatalog::describe($name),
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $this->permissions
        );

        Permission::query()->upsert($rows, ['name', 'guard_name'], ['description', 'updated_at']);

        $adminRole = Role::query()
            ->where('name', 'Admin')
            ->where('guard_name', 'web')
            ->first();
        if ($adminRole) {
            $permissionIds = Permission::query()
                ->whereIn('name', $this->permissions)
                ->where('guard_name', 'web')
                ->pluck('id')
                ->all();

            $rows = array_map(
                fn (int $permissionId) => [
                    'permission_id' => $permissionId,
                    'role_id' => $adminRole->id,
                ],
                $permissionIds
            );

            if (! empty($rows)) {
                DB::table('role_has_permissions')->upsert(
                    $rows,
                    ['permission_id', 'role_id'],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->delete();
    }
};
