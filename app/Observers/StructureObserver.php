<?php

namespace App\Observers;

use App\Models\Structure;
use App\Support\OrderLookupCache;
use App\Support\PersonnelDropdownCache;
use App\Traits\ObservableTrait;

class StructureObserver
{
    use ObservableTrait;

    /**
     * Cache keys that should be flushed whenever the structure tree changes.
     *
     * @var array<int,string>
     */
    protected array $caches = [
        'structures',
        'staff:structures',
        'candidate:structures',
        'businessTrips:structures',
        'personnel:structures',
    ];
    /**
     * Handle the Structure "created" event.
     */
    public function created(Structure $structure): void
    {
        $this->flushRelatedCaches();
    }

    /**
     * Handle the Structure "updated" event.
     */
    public function updated(Structure $structure): void
    {
        $this->flushRelatedCaches();
    }

    /**
     * Handle the Structure "deleted" event.
     */
    public function deleted(Structure $structure): void
    {
        $this->flushRelatedCaches();
    }

    /**
     * Handle the Structure "restored" event.
     */
    public function restored(Structure $structure): void
    {
        $this->flushRelatedCaches();
    }

    /**
     * Handle the Structure "force deleted" event.
     */
    public function forceDeleted(Structure $structure): void
    {
        $this->flushRelatedCaches();
    }

    protected function flushRelatedCaches(): void
    {
        $this->clearCaches();
        PersonnelDropdownCache::forgetStructures();
        OrderLookupCache::bump('main_structures');
        OrderLookupCache::bump('structures');
    }
}
