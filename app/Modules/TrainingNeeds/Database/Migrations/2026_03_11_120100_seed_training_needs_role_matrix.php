<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * @var array<string,array<int,string>>
     */
    private array $rolePermissionMap = [
        'HR Admin' => [
            'show-training-needs',
            'manage-training-needs',
            'review-training-needs',
            'export-training-needs',
        ],
        'HR Manager' => [
            'show-training-needs',
            'manage-training-needs',
            'review-training-needs',
            'export-training-needs',
        ],
        'HR Employee' => [
            'show-training-needs',
            'manage-training-needs',
        ],
        'HR Auditor' => [
            'show-training-needs',
            'export-training-needs',
        ],
    ];

    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $now = Carbon::now();
        $permissionIdsByName = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->allPermissions())
            ->pluck('id', 'name');

        foreach ($this->rolePermissionMap as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now]
            );

            $rows = [];
            foreach ($permissions as $permissionName) {
                $permissionId = (int) ($permissionIdsByName[$permissionName] ?? 0);
                if ($permissionId <= 0) {
                    continue;
                }

                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => (int) $role->id,
                ];
            }

            if (! empty($rows)) {
                DB::table('role_has_permissions')->upsert($rows, ['permission_id', 'role_id'], []);
            }
        }
    }

    public function down(): void
    {
        $roleIds = Role::query()
            ->whereIn('name', array_keys($this->rolePermissionMap))
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        $permissionIds = Permission::query()
            ->whereIn('name', $this->allPermissions())
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        if (! empty($roleIds) && ! empty($permissionIds)) {
            DB::table('role_has_permissions')
                ->whereIn('role_id', $roleIds)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }

    private function allPermissions(): array
    {
        return collect($this->rolePermissionMap)->flatten()->unique()->values()->all();
    }
};
