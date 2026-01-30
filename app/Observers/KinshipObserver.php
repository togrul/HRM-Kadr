<?php

namespace App\Observers;

use App\Models\Kinship;
use App\Support\PersonnelDropdownCache;

class KinshipObserver
{
    public function saved(Kinship $kinship): void
    {
        PersonnelDropdownCache::forgetKinships();
    }

    public function deleted(Kinship $kinship): void
    {
        $this->saved($kinship);
    }
}
