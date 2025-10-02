<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class StructureService
{
    public function getAccessibleStructures(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        // return $user->structures()
        //         ->pluck('structures.id')
        //         ->all();
        return Cache::remember(
            "structure-accessible-{$user->id}",
            now()->addMinutes(5),
            fn () => $user->structures()
                ->pluck('structures.id')
                ->all()
        );
    }

//     public function getAccessibleStructures(): array
//     {
//         return auth()->user()
//             ->structures
//             ->pluck('id')
//             ->unique()
//             ->toArray();

// //        return auth()->user()
// //            ->roles()
// //            ->with('structures:id')
// //            ->get()
// //            ->flatMap(fn ($role) => $role->structures->pluck('id'))
// //            ->unique()
// //            ->toArray();
//     }
}
