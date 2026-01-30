<?php

namespace App\Observers;

use App\Models\Rank;
use App\Support\OrderLookupCache;
use App\Support\PersonnelDropdownCache;

class RankObserver
{
    public function saved(Rank $rank): void
    {
        PersonnelDropdownCache::forgetRanks();
        OrderLookupCache::bump('ranks');
    }

    public function deleted(Rank $rank): void
    {
        $this->saved($rank);
    }
}
