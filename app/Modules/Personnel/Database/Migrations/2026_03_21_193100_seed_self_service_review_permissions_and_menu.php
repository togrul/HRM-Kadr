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
     * @var array<int, string>
     */
    private array $roles = ['Admin', 'HR Admin', 'HR Manager', 'HR Employee'];

    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $now = Carbon::now();
        $permissions = [
            'review-self-service-requests',
            'request-own-request-correction',
        ];

        $created = [];

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'description' => PermissionDescriptionCatalog::describe($permissionName),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $created[$permissionName] = $permission->id;
        }

        $roleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->roles)
            ->pluck('id')
            ->all();

        if ($roleIds !== []) {
            $rows = [];

            foreach ($roleIds as $roleId) {
                foreach (['review-self-service-requests'] as $permissionName) {
                    $rows[] = [
                        'role_id' => $roleId,
                        'permission_id' => $created[$permissionName],
                    ];
                }
            }

            DB::table('role_has_permissions')->upsert($rows, ['role_id', 'permission_id'], []);
        }

        $employeeRoleId = Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'Employee Self-Service')
            ->value('id');

        if ($employeeRoleId) {
            DB::table('role_has_permissions')->upsert([
                [
                    'role_id' => $employeeRoleId,
                    'permission_id' => $created['request-own-request-correction'],
                ],
            ], ['role_id', 'permission_id'], []);
        }

        Menu::query()->updateOrCreate(
            ['name' => 'ui::menu.items.self_service_reviews'],
            [
                'icon' => 'comment-icon',
                'color' => 'zinc',
                'order' => 13,
                'is_active' => 1,
                'url' => 'self-service-reviews',
                'permission_id' => $created['review-self-service-requests'],
            ]
        );
    }

    public function down(): void
    {
        $permissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['review-self-service-requests', 'request-own-request-correction'])
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            Permission::query()->whereIn('id', $permissionIds)->delete();
        }

        Menu::query()
            ->where('url', 'self-service-reviews')
            ->orWhere('name', 'ui::menu.items.self_service_reviews')
            ->delete();
    }
};
