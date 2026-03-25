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
     * @var array<int, string>
     */
    private array $roles = ['Admin', 'HR Admin', 'HR Manager', 'HR Employee'];

    public function up(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        $permission = Permission::query()->updateOrCreate(
            ['name' => 'review-all-self-service-requests', 'guard_name' => 'web'],
            [
                'description' => PermissionDescriptionCatalog::describe('review-all-self-service-requests'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        $roleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->roles)
            ->pluck('id')
            ->all();

        if ($roleIds !== []) {
            $rows = array_map(fn (int $roleId): array => [
                'role_id' => $roleId,
                'permission_id' => $permission->id,
            ], $roleIds);

            DB::table('role_has_permissions')->upsert($rows, ['role_id', 'permission_id'], []);
        }
    }

    public function down(): void
    {
        $permissionId = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'review-all-self-service-requests')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('role_has_permissions')->where('permission_id', $permissionId)->delete();
        Permission::query()->whereKey($permissionId)->delete();
    }
};
