<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StructureService
{
    public function getAccessibleStructures(): array
    {
        return auth()->user()
            ->structures
            ->pluck('id')
            ->unique()
            ->toArray();

//        return auth()->user()
//            ->roles()
//            ->with('structures:id')
//            ->get()
//            ->flatMap(fn ($role) => $role->structures->pluck('id'))
//            ->unique()
//            ->toArray();
    }
}
