<?php

namespace App\Observers;

use App\Models\Award;
use App\Support\PersonnelDropdownCache;

class AwardObserver
{
    public function saved(Award $award): void
    {
        PersonnelDropdownCache::forgetAwards();
    }

    public function deleted(Award $award): void
    {
        $this->saved($award);
    }
}
