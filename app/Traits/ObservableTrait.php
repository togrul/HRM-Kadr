<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait ObservableTrait
{
    protected function clearCaches()
    {
        foreach ($this->caches as $cache) {
            Cache::forget($cache);
        }
    }
}