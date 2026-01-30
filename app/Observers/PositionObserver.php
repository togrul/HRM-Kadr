<?php

namespace App\Observers;

use App\Models\Position;
use App\Observers\Concerns\FlushesCallPersonnelCache;
use App\Support\OrderLookupCache;
use App\Support\PersonnelDropdownCache;
use Illuminate\Support\Facades\Cache;

class PositionObserver
{
    use FlushesCallPersonnelCache;

    public function saved(Position $position): void
    {
        $this->forgetCallPersonnelCache('positions');
        Cache::forget('staff:positions');
        PersonnelDropdownCache::forgetPositions();
        OrderLookupCache::bump('positions');
    }

    public function deleted(Position $position): void
    {
        $this->saved($position);
    }
}
