<?php

namespace App\Services;

use App\Models\RoleStructure;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class StructureService
{
    /**
     * Structures the user may see: the union of the structures granted to each of the
     * user's roles (role_structures is keyed by role, and is edited per role).
     *
     * @return list<int>
     */
    public function getAccessibleStructures(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        // ponytail: cached per user for 5 minutes; RoleStructureObserver clears it when a
        // role's structures change, a changed role assignment waits out the TTL.
        return Cache::remember(
            "structure-accessible-{$user->id}",
            now()->addMinutes(5),
            fn (): array => RoleStructure::query()
                ->whereIn('role_id', $user->roles()->select('roles.id'))
                ->distinct()
                ->pluck('structure_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all()
        );
    }
}
