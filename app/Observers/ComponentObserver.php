<?php

namespace App\Observers;

use App\Models\Component;
use App\Support\OrderLookupCache;

class ComponentObserver
{
    public function saved(Component $component): void
    {
        OrderLookupCache::bump('components');
    }

    public function deleted(Component $component): void
    {
        $this->saved($component);
    }
}
