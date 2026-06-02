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
    private string $permission = 'show-audit-logs';

    private array $roles = ['Admin', 'HR Admin', 'HR Auditor'];

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
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $roleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->roles)
            ->pluck('id')
            ->all();

        if ($roleIds !== []) {
            DB::table('role_has_permissions')->upsert(
                array_map(fn (int $roleId): array => [
                    'role_id' => $roleId,
                    'permission_id' => $permission->id,
                ], $roleIds),
                ['role_id', 'permission_id'],
                []
            );
        }

        Menu::query()->updateOrCreate(
            ['name' => 'ui::menu.items.audit_logs'],
            [
                'icon' => 'shield-icon',
                'color' => 'zinc',
                'order' => 13,
                'is_active' => 1,
                'url' => 'audit.logs',
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

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            Permission::query()->whereIn('id', $permissionIds)->delete();
        }

        Menu::query()
            ->where('url', 'audit.logs')
            ->orWhere('name', 'ui::menu.items.audit_logs')
            ->delete();
    }
};
