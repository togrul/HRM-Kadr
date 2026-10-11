<?php

namespace App\Observers;

use App\Models\City;
use App\Support\PersonnelDropdownCache;
use Illuminate\Support\Facades\Cache;

class CityObserver
{
    public function saved(City $city): void
    {
        Cache::forget('city:baki');
        PersonnelDropdownCache::forgetCities($city->country_id, $city->getOriginal('country_id'));
    }

    public function deleted(City $city): void
    {
        $this->saved($city);
    }
}
