<?php

namespace App\Observers;

use App\Models\ScientificDegreeAndName;
use App\Support\PersonnelDropdownCache;

class ScientificDegreeObserver
{
    public function saved(ScientificDegreeAndName $degree): void
    {
        PersonnelDropdownCache::forgetScientificDegrees();
    }

    public function deleted(ScientificDegreeAndName $degree): void
    {
        $this->saved($degree);
    }
}
