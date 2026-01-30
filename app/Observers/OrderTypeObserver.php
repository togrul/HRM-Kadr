<?php

namespace App\Observers;

use App\Models\OrderType;
use App\Support\OrderLookupCache;
use Illuminate\Support\Facades\Cache;

class OrderTypeObserver
{
    public function saved(OrderType $type): void
    {
        OrderLookupCache::bump('templates');
        Cache::forget('businessTrips:order_types');
        Cache::forget('component:order_type');
    }

    public function deleted(OrderType $type): void
    {
        $this->saved($type);
    }
}
