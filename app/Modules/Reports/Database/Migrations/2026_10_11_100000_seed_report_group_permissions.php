<?php

use App\Support\Permissions\PermissionDescriptionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Hesabatlar modulunun rol matrisi: hesabat qrupları üzrə baxış, paylaşılan preset-lərin
 * idarəsi və planlı hesabatlar.
 *
 * Geriyə uyğunluq: əvvəllər `show-reports`/`export-reports` bütün hesabatları açırdı.
 * Həmin icazəsi olan hər rol və istifadəçi (birbaşa icazə) qrup icazələrini alır ki, heç kim
 * əvvəl gördüyünü itirməsin. Əmək haqqı qrupu yalnız `show-payroll` olanlara verilir —
 * onsuz da maaş icazəsi olmadan açılmır. Preset paylaşımı və planlı göndəriş yeni
 * imkanlardır: yalnız Admin rolu alır, qalanlarına rol ekranından verilir.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $legacyGroups = [
        'show-personnel-reports',
        'show-attendance-reports',
        'show-leave-reports',
        'show-training-reports',
        'show-performance-reports',
    ];

    private string $payrollGroup = 'show-payroll-reports';

    /**
     * @var list<string>
     */
    private array $newCapabilities = [
        'manage-report-presets',
        'schedule-reports',
    ];

    public function up(): void
    {
        $now = Carbon::now();
        $names = $this->permissionNames();

        Permission::query()->upsert(
            array_map(fn (string $name): array => [
                'name' => $name,
                'description' => PermissionDescriptionCatalog::describe($name),
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ], $names),
            ['name', 'guard_name'],
            ['description', 'updated_at']
        );

        $ids = Permission::query()->where('guard_name', 'web')->pluck('id', 'name');
        $legacyIds = array_values(array_filter([$ids['show-reports'] ?? null, $ids['export-reports'] ?? null]));
        $payrollViewId = $ids['show-payroll'] ?? null;

        $groupIds = array_values(array_filter(array_map(fn (string $name) => $ids[$name] ?? null, $this->legacyGroups)));
        $payrollGroupId = $ids[$this->payrollGroup] ?? null;

        if ($legacyIds !== []) {
            $this->grantToRoles($this->rolesWith($legacyIds), $groupIds);
            $this->grantToModels($this->modelsWith($legacyIds), $groupIds);

            if ($payrollGroupId !== null && $payrollViewId !== null) {
                $this->grantToRoles(array_values(array_intersect($this->rolesWith($legacyIds), $this->rolesWith([$payrollViewId]))), [$payrollGroupId]);
                $this->grantToModels($this->modelsWithBoth($legacyIds, $payrollViewId), [$payrollGroupId]);
            }
        }

        $adminRoleId = DB::table('roles')->where('name', 'Admin')->where('guard_name', 'web')->value('id');
        if ($adminRoleId !== null) {
            $this->grantToRoles([(int) $adminRoleId], array_values(array_filter(array_map(fn (string $name) => $ids[$name] ?? null, $names))));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', $this->permissionNames())
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    private function permissionNames(): array
    {
        return [...$this->legacyGroups, $this->payrollGroup, ...$this->newCapabilities];
    }

    /**
     * @param  list<int>  $permissionIds
     * @return list<int>
     */
    private function rolesWith(array $permissionIds): array
    {
        return DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->distinct()
            ->pluck('role_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $permissionIds
     * @return list<array{model_type:string,model_id:int}>
     */
    private function modelsWith(array $permissionIds): array
    {
        return DB::table('model_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->select('model_type', 'model_id')
            ->distinct()
            ->get()
            ->map(fn ($row): array => ['model_type' => (string) $row->model_type, 'model_id' => (int) $row->model_id])
            ->all();
    }

    /**
     * @param  list<int>  $permissionIds
     * @return list<array{model_type:string,model_id:int}>
     */
    private function modelsWithBoth(array $permissionIds, int $requiredId): array
    {
        $required = collect($this->modelsWith([$requiredId]))
            ->map(fn (array $model): string => $model['model_type'].'#'.$model['model_id'])
            ->all();

        return array_values(array_filter(
            $this->modelsWith($permissionIds),
            fn (array $model): bool => in_array($model['model_type'].'#'.$model['model_id'], $required, true)
        ));
    }

    /**
     * @param  list<int>  $roleIds
     * @param  list<int>  $permissionIds
     */
    private function grantToRoles(array $roleIds, array $permissionIds): void
    {
        $rows = [];
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $rows[] = ['permission_id' => $permissionId, 'role_id' => $roleId];
            }
        }

        if ($rows !== []) {
            // Yenilənəcək sütun yoxdur: upsert(..., []) adi insert-ə düşür və artıq olan
            // cütdə (məs. Admin həm köhnə, həm yeni yoldan alır) MySQL-də dublikat xətası verir.
            DB::table('role_has_permissions')->insertOrIgnore($rows);
        }
    }

    /**
     * @param  list<array{model_type:string,model_id:int}>  $models
     * @param  list<int>  $permissionIds
     */
    private function grantToModels(array $models, array $permissionIds): void
    {
        $rows = [];
        foreach ($models as $model) {
            foreach ($permissionIds as $permissionId) {
                $rows[] = ['permission_id' => $permissionId] + $model;
            }
        }

        if ($rows !== []) {
            DB::table('model_has_permissions')->insertOrIgnore($rows);
        }
    }
};
