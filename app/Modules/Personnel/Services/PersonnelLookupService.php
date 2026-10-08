<?php

namespace App\Modules\Personnel\Services;

use App\Models\Country;
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

    /**
     * Azərbaycanın `countries` cədvəlindəki id-si: yeni işçinin vətəndaşlığı üçün
     * susmaya görə dəyər və siyahıda birinci göstərilən ölkə.
     */
    public function homeCountryId(): ?int
    {
        $id = Country::query()->where('code', 'AZ')->value('id');

        return $id === null ? null : (int) $id;
    }
}
