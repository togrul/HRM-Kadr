<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'manage-order-template-sets',
        'manage-order-template-metadata',
        'manage-order-template-versions',
    ];

    public function up(): void
    {
        $now = now();
        $hasDescriptionColumn = Schema::hasColumn('permissions', 'description');

        $rows = collect($this->permissions)
            ->map(function (string $name) use ($hasDescriptionColumn, $now): array {
                $row = [
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasDescriptionColumn) {
                    $row['description'] = PermissionDescriptionCatalog::describe($name);
                }

                return $row;
            })
            ->all();

        $updateColumns = ['updated_at'];

        if ($hasDescriptionColumn) {
            $updateColumns[] = 'description';
        }

        DB::table('permissions')->upsert($rows, ['name', 'guard_name'], $updateColumns);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
