<?php

namespace App\Services;

use App\Models\RoleStructure;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * İstifadəçi və rol idarəçiliyində imtiyaz artırmanın qarşısını alan qaydalar.
 *
 * - `manage-users` istifadəçi hesablarını, `manage-roles` rolları və icazələri idarə edir;
 *   `access-settings` yalnız tənzimləmələr bölməsinə girişdir.
 * - İdarəçi özündə olmayan icazəyə malik istifadəçini dəyişə/silə bilməz.
 * - İdarəçi özündə olmayan icazəni ehtiva edən rolu təyin edə, belə icazəni rola verə bilməz.
 * - İdarəçi öz rolunu və öz rolunun icazələrini dəyişə bilməz.
 * - Kodda adı ilə istinad edilən sistem rollarının adı dəyişdirilə və silinə bilməz.
 */
class UserAdministrationGuard
{
    public const MANAGE_USERS = 'manage-users';

    public const MANAGE_ROLES = 'manage-roles';

    /**
     * Kodda adı ilə axtarılan rollar (hasRole('Admin'), Employee Self-Service kabineti,
     * bildiriş auditoriyası 'admin' / 'hr', miqrasiyaların rol matrisi 'HR Admin').
     *
     * @var list<string>
     */
    public const SYSTEM_ROLES = ['Admin', 'HR Admin', 'Employee Self-Service', 'hr'];

    public function isSystemRole(?string $name): bool
    {
        $name = mb_strtolower(trim((string) $name));

        foreach (self::SYSTEM_ROLES as $systemRole) {
            if (mb_strtolower($systemRole) === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, string>
     */
    public function permissionNamesOf(User $user): Collection
    {
        return $user->getAllPermissions()->pluck('name')->map(fn ($name): string => (string) $name)->unique()->values();
    }

    /**
     * $permissionNames içindən idarəçidə OLMAYANLAR.
     *
     * @param  iterable<int, string>  $permissionNames
     * @return list<string>
     */
    public function missingPermissions(User $actor, iterable $permissionNames): array
    {
        $held = $this->permissionNamesOf($actor)->flip();

        return collect($permissionNames)
            ->map(fn ($name): string => (string) $name)
            ->reject(fn (string $name): bool => $held->has($name))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Hədəf istifadəçinin hər icazəsi idarəçidə də varsa, onu redaktə/silmək olar.
     */
    public function canManageUser(User $actor, User $target): bool
    {
        if (! $actor->can(self::MANAGE_USERS)) {
            return false;
        }

        $structures = app(StructureService::class);
        $actorScope = $structures->scopeFor($actor);
        $targetScope = $structures->scopeFor($target);

        return $this->missingPermissions($actor, $this->permissionNamesOf($target)) === []
            && ($actorScope->isAll() || (! $targetScope->isAll() && $actorScope->allowsAll($targetScope->ids())));
    }

    /**
     * Rolun bütün icazələri idarəçidə varsa, onu təyin etmək olar.
     */
    public function canAssignRole(User $actor, Role $role): bool
    {
        if (! $actor->can(self::MANAGE_USERS)) {
            return false;
        }

        if ($this->missingPermissions($actor, $role->permissions()->pluck('name')) !== []) {
            return false;
        }

        return $this->canAssignRoleStructures($actor, $role);
    }

    public function canAssignRoleStructures(User $actor, Role $role): bool
    {
        $scope = app(StructureService::class)->scopeFor($actor);
        if ($scope->isAll()) {
            return true;
        }

        return ! (bool) $role->getAttribute('all_structures') && $scope->allowsAll(
            RoleStructure::query()->where('role_id', $role->getKey())->pluck('structure_id'),
        );
    }

    /**
     * Rolun icazələrini dəyişmək: öz rolu olmamalı, rolun mövcud və yeni icazələri
     * idarəçidə olmalıdır.
     *
     * @param  iterable<int, string>  $newPermissionNames
     */
    public function canEditRolePermissions(User $actor, Role $role, iterable $newPermissionNames = []): bool
    {
        if (! $actor->can(self::MANAGE_ROLES) || $this->holdsRole($actor, $role)) {
            return false;
        }

        $current = $role->permissions()->pluck('name');

        return $this->missingPermissions($actor, collect($current)->merge(collect($newPermissionNames))) === [];
    }

    public function holdsRole(User $actor, Role $role): bool
    {
        return $actor->roles()->whereKey($role->getKey())->exists();
    }

    public function passwordMatches(User $actor, ?string $password): bool
    {
        return is_string($password) && $password !== '' && Hash::check($password, (string) $actor->getAuthPassword());
    }
}
