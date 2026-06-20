<?php

use App\Models\Menu;
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
        'show-reports',
        'export-reports',
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

        $adminRole = Role::query()->where('name', 'Admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $permissionIds = Permission::query()
                ->whereIn('name', $this->permissions)
                ->where('guard_name', 'web')
                ->pluck('id')
                ->all();

            $pivotRows = array_map(
                fn (int $permissionId) => ['permission_id' => $permissionId, 'role_id' => $adminRole->id],
                $permissionIds
            );

            if ($pivotRows !== []) {
                DB::table('role_has_permissions')->upsert($pivotRows, ['permission_id', 'role_id'], []);
            }
        }

        $showReportsId = Permission::query()
            ->where('name', 'show-reports')
            ->where('guard_name', 'web')
            ->value('id');

        Menu::query()
            ->whereIn('name', ['ui::menu.items.reports', 'Reports', 'Hesabatlar'])
            ->update([
                'url' => 'reports',
                'permission_id' => $showReportsId,
            ]);
    }

    public function down(): void
    {
        Menu::query()
            ->whereIn('name', ['ui::menu.items.reports', 'Reports', 'Hesabatlar'])
            ->update(['url' => 'home']);

        Permission::query()
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->delete();
    }
};
