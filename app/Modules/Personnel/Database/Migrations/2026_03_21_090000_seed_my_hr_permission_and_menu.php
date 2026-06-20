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
    private string $permission = 'show-my-hr';

    /**
     * @var array<int, string>
     */
    private array $roles = [
        'Admin',
        'HR Admin',
        'HR Manager',
        'HR Employee',
    ];

    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $now = Carbon::now();

        $permission = Permission::query()->updateOrCreate(
            ['name' => $this->permission, 'guard_name' => 'web'],
            [
                'description' => PermissionDescriptionCatalog::describe($this->permission),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $this->assignRoles($permission->id);

        Menu::query()->updateOrCreate(
            ['name' => 'ui::menu.items.my_hr'],
            [
                'icon' => 'profile-outline-icon',
                'color' => 'zinc',
                'order' => 7,
                'is_active' => 1,
                'url' => 'my-hr',
                'permission_id' => $permission->id,
            ]
        );
    }

    public function down(): void
    {
        $permissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', $this->permission)
            ->pluck('id')
            ->all();

        if (! empty($permissionIds)) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        Menu::query()
            ->where('url', 'my-hr')
            ->orWhere('name', 'ui::menu.items.my_hr')
            ->delete();

        Permission::query()
            ->where('guard_name', 'web')
            ->where('name', $this->permission)
            ->delete();
    }

    private function assignRoles(int $permissionId): void
    {
        $roleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->roles)
            ->pluck('id')
            ->all();

        $rows = array_map(
            fn (int $roleId) => [
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ],
            $roleIds
        );

        if (! empty($rows)) {
            DB::table('role_has_permissions')->upsert($rows, ['permission_id', 'role_id'], []);
        }
    }
};
