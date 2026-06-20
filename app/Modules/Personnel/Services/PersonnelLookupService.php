<?php

namespace App\Modules\Personnel\Services;

use App\Models\Position;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PersonnelLookupService
{
    public function positions(): Collection
    {
        return Cache::remember(
            'personnel:positions:list',
            now()->addMinutes(30),
            function () {
                return Position::query()
                    ->select('id', 'name')
                    ->orderBy('id')
                    ->get();
            }
        );
    }
}

