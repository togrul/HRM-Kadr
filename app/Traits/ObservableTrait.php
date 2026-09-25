<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait ObservableTrait
{
    protected function clearCaches(): void
    {
        foreach ($this->caches as $cache) {
            Cache::forget($cache);
        }
    }
}
