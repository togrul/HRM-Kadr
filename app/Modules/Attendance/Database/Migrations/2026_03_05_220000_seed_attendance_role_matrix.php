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
            'show-attendance',
            'manage-attendance',
            'add-attendance-manual',
            'edit-attendance-manual',
            'approve-attendance-manual',
            'approve-attendance-overtime',
            'manage-attendance-month-close',
            'edit-attendance-exceptions',
            'export-attendance',
        ],
        'HR Manager' => [
            'show-attendance',
            'add-attendance-manual',
            'edit-attendance-manual',
            'approve-attendance-manual',
            'approve-attendance-overtime',
            'edit-attendance-exceptions',
            'export-attendance',
        ],
        'HR Employee' => [
            'show-attendance',
        ],
        'HR Auditor' => [
            'show-attendance',
            'export-attendance',
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

    /**
     * @return array<int,string>
     */
    private function allPermissions(): array
    {
        return collect($this->rolePermissionMap)
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }
};

