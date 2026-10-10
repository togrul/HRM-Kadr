<?php

namespace App\Console\Commands;

use App\Services\StructureService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Struktur görünürlüyünün fail closed qaydasına keçiddən əvvəl/sonra rolların vəziyyəti:
 * hər rol üzrə istifadəçi sayı, struktur sayı, «bütün strukturlar» bayrağı, yetim
 * role_structures sətirləri və heç nə görməyəcək istifadəçilər. Yalnız oxuyur.
 */
class SecurityAuditRoleStructuresCommand extends Command
{
    protected $signature = 'security:audit-role-structures {--json : Hesabatı JSON kimi çap et}';

    protected $description = 'Rolların struktur görünürlüyünü yoxlayır: struktur sayı, «bütün strukturlar» bayrağı, yetim sətirlər və heç nə görməyən istifadəçilər';

    public function handle(): int
    {
        $hasFlag = StructureService::supportsAllStructuresFlag();
        $roles = $this->roles($hasFlag);
        $orphans = $this->orphanRows();
        $blind = $this->usersWhoSeeNothing($hasFlag);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'all_structures_column' => $hasFlag,
                'roles' => $roles->all(),
                'orphan_role_structures' => $orphans->all(),
                'users_seeing_nothing' => $blind->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if (! $hasFlag) {
            $this->warn('roles.all_structures sütunu yoxdur — miqrasiya hələ işlədilməyib. Bayraq sütunu «xeyr» kimi göstərilir.');
        }

        $this->info('Rollar');
        $this->table(
            ['ID', 'Rol', 'İstifadəçi', 'Struktur sayı', 'Bütün strukturlar', 'Nəticə'],
            $roles->map(fn (array $role): array => [
                $role['id'],
                $role['name'],
                $role['users'],
                $role['structures'],
                $role['all_structures'] ? 'bəli' : 'xeyr',
                $role['all_structures'] ? 'hamısını görür' : ($role['structures'] > 0 ? 'məhdud' : 'HEÇ NƏ görmür'),
            ])->all()
        );

        $this->info('Yetim role_structures sətirləri (rol və ya struktur artıq yoxdur)');
        $orphans->isEmpty()
            ? $this->line('  yoxdur')
            : $this->table(['role_id', 'structure_id', 'Səbəb'], $orphans->map(fn (array $row): array => array_values($row))->all());

        $this->info('Heç bir işçi qeydini görməyəcək istifadəçilər');
        $blind->isEmpty()
            ? $this->line('  yoxdur')
            : $this->table(
                ['ID', 'Ad', 'E-poçt', 'Rollar', 'İşçi siyahısı icazəsi'],
                $blind->map(fn (array $user): array => [
                    $user['id'],
                    $user['name'],
                    $user['email'],
                    $user['roles'] === [] ? '—' : implode(', ', $user['roles']),
                    $user['has_show_personnels'] ? 'bəli — yoxla' : 'xeyr',
                ])->all()
            );

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, array{id:int, name:string, users:int, structures:int, all_structures:bool}>
     */
    private function roles(bool $hasFlag): Collection
    {
        $users = DB::table('model_has_roles')
            ->where('model_type', config('auth.providers.users.model', \App\Models\User::class))
            ->selectRaw('role_id, count(*) as aggregate')
            ->groupBy('role_id')
            ->pluck('aggregate', 'role_id');

        $structures = DB::table('role_structures')
            ->join('structures', 'structures.id', '=', 'role_structures.structure_id')
            ->selectRaw('role_structures.role_id, count(distinct role_structures.structure_id) as aggregate')
            ->groupBy('role_structures.role_id')
            ->pluck('aggregate', 'role_id');

        return DB::table('roles')
            ->orderBy('id')
            ->get($hasFlag ? ['id', 'name', 'all_structures'] : ['id', 'name'])
            ->map(fn (object $role): array => [
                'id' => (int) $role->id,
                'name' => (string) $role->name,
                'users' => (int) ($users[$role->id] ?? 0),
                'structures' => (int) ($structures[$role->id] ?? 0),
                'all_structures' => (bool) ($role->all_structures ?? false),
            ]);
    }

    /**
     * @return Collection<int, array{role_id:int, structure_id:int, reason:string}>
     */
    private function orphanRows(): Collection
    {
        return DB::table('role_structures')
            ->leftJoin('roles', 'roles.id', '=', 'role_structures.role_id')
            ->leftJoin('structures', 'structures.id', '=', 'role_structures.structure_id')
            ->where(fn ($query) => $query->whereNull('roles.id')->orWhereNull('structures.id'))
            ->orderBy('role_structures.role_id')
            ->get(['role_structures.role_id', 'role_structures.structure_id', 'roles.id as existing_role', 'structures.id as existing_structure'])
            ->map(fn (object $row): array => [
                'role_id' => (int) $row->role_id,
                'structure_id' => (int) $row->structure_id,
                'reason' => $row->existing_role === null ? 'rol yoxdur' : 'struktur yoxdur',
            ]);
    }

    /**
     * Rollarının heç birində bayraq və mövcud struktur olmayan aktiv istifadəçilər.
     *
     * @return Collection<int, array{id:int, name:string, email:string, roles:list<string>, has_show_personnels:bool}>
     */
    private function usersWhoSeeNothing(bool $hasFlag): Collection
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        $seeing = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $userModel)
            ->where(function ($query) use ($hasFlag): void {
                if ($hasFlag) {
                    $query->where('roles.all_structures', true);
                }

                $query->orWhereExists(fn ($exists) => $exists->selectRaw('1')
                    ->from('role_structures')
                    ->join('structures', 'structures.id', '=', 'role_structures.structure_id')
                    ->whereColumn('role_structures.role_id', 'roles.id'));
            })
            ->distinct()
            ->pluck('model_has_roles.model_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $roleNames = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $userModel)
            ->get(['model_has_roles.model_id', 'roles.name'])
            ->groupBy('model_id')
            ->map(fn (Collection $rows): array => array_values($rows->pluck('name')->map(fn ($name): string => (string) $name)->all()));

        $showPersonnels = DB::table('permissions')->where('name', 'show-personnels')->value('id');
        $withShowPersonnels = $showPersonnels === null ? [] : DB::table('model_has_permissions')
            ->where('permission_id', $showPersonnels)
            ->where('model_type', $userModel)
            ->pluck('model_id')
            ->merge(
                DB::table('model_has_roles')
                    ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
                    ->where('role_has_permissions.permission_id', $showPersonnels)
                    ->where('model_has_roles.model_type', $userModel)
                    ->pluck('model_has_roles.model_id')
            )
            ->map(fn ($id): int => (int) $id)
            ->flip()
            ->all();

        return DB::table('users')
            ->when($seeing !== [], fn ($query) => $query->whereNotIn('id', $seeing))
            ->when(DB::getSchemaBuilder()->hasColumn('users', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->when(DB::getSchemaBuilder()->hasColumn('users', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('id')
            ->get(['id', 'name', 'email'])
            ->map(fn (object $user): array => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'roles' => $roleNames[$user->id] ?? [],
                'has_show_personnels' => isset($withShowPersonnels[(int) $user->id]),
            ]);
    }
}
