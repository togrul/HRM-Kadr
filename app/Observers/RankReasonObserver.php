<?php

namespace App\Observers;

use App\Models\RankReason;
use App\Observers\Concerns\FlushesCallPersonnelCache;
use App\Support\PersonnelDropdownCache;

class RankReasonObserver
{
    use FlushesCallPersonnelCache;

    public function saved(RankReason $reason): void
    {
        $this->forgetCallPersonnelCache('rank_reasons');
        PersonnelDropdownCache::forgetRankReasons();
    }

    public function deleted(RankReason $reason): void
    {
        $this->saved($reason);
    }
}
