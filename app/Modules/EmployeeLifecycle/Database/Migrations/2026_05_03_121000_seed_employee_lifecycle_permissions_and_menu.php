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
    private array $permissions = ['show-employee-lifecycle', 'manage-employee-lifecycle'];

    private array $roles = ['Admin', 'HR Admin'];

    public function up(): void
    {
        $now = Carbon::now();
        $permissions = collect($this->permissions)
            ->mapWithKeys(fn (string $name): array => [
                $name => Permission::query()->updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    [
                        'description' => PermissionDescriptionCatalog::describe($name),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                ),
            ]);

        $roleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->roles)
            ->pluck('id')
            ->all();

        if ($roleIds !== []) {
            DB::table('role_has_permissions')->upsert(
                collect($roleIds)
                    ->flatMap(fn (int $roleId) => $permissions->map(fn (Permission $permission): array => [
                        'role_id' => $roleId,
                        'permission_id' => $permission->id,
                    ]))
                    ->values()
                    ->all(),
                ['role_id', 'permission_id'],
                []
            );
        }

        Menu::query()->updateOrCreate(
            ['name' => 'ui::menu.items.employee_lifecycle'],
            [
                'icon' => 'refresh-icon',
                'color' => 'zinc',
                'order' => 15,
                'is_active' => 1,
                'url' => 'employee-lifecycle',
                'permission_id' => $permissions->get('show-employee-lifecycle')->id,
            ]
        );
    }

    public function down(): void
    {
        $permissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            Permission::query()->whereIn('id', $permissionIds)->delete();
        }

        Menu::query()
            ->where('url', 'employee-lifecycle')
            ->orWhere('name', 'ui::menu.items.employee_lifecycle')
            ->delete();
    }
};
