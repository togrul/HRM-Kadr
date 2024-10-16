<?php

namespace App\Observers;

use App\Models\Structure;
use App\Traits\ObservableTrait;

class StructureObserver
{
    use ObservableTrait;

    private $caches = [
        'structures',
    ];
    /**
     * Handle the Structure "created" event.
     */
    public function created(Structure $structure): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Structure "updated" event.
     */
    public function updated(Structure $structure): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Structure "deleted" event.
     */
    public function deleted(Structure $structure): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Structure "restored" event.
     */
    public function restored(Structure $structure): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Structure "force deleted" event.
     */
    public function forceDeleted(Structure $structure): void
    {
        $this->clearCaches();
    }
}
