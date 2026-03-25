<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private string $employeeRole = 'Employee Self-Service';

    public function up(): void
    {
        $permissions = [
            'submit-self-service-leaves',
            'submit-self-service-vacations',
            'submit-self-service-business-trips',
        ];

        foreach ($permissions as $permission) {
            $record = Permission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );

            if (DB::getSchemaBuilder()->hasColumn('permissions', 'description')) {
                $record->forceFill([
                    'description' => PermissionDescriptionCatalog::describe($permission),
                ])->save();
            }
        }

        $employeeRole = Role::query()->where('guard_name', 'web')->where('name', $this->employeeRole)->first();

        if (! $employeeRole) {
            return;
        }

        $employeeRole->givePermissionTo($permissions);
    }

    public function down(): void
    {
        $employeeRole = Role::query()->where('guard_name', 'web')->where('name', $this->employeeRole)->first();

        if ($employeeRole) {
            $employeeRole->revokePermissionTo([
                'submit-self-service-leaves',
                'submit-self-service-vacations',
                'submit-self-service-business-trips',
            ]);
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'submit-self-service-leaves',
                'submit-self-service-vacations',
                'submit-self-service-business-trips',
            ])
            ->delete();
    }
};
