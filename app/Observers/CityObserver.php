<?php

namespace App\Observers;

use App\Models\City;
use Illuminate\Support\Facades\Cache;

class CityObserver
{
    public function saved(City $city): void
    {
        Cache::forget('city:baki');
    }

    public function deleted(City $city): void
    {
        $this->saved($city);
    }
}
