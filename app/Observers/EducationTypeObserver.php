<?php

namespace App\Observers;

use App\Models\EducationType;
use App\Support\PersonnelDropdownCache;

class EducationTypeObserver
{
    public function saved(EducationType $type): void
    {
        PersonnelDropdownCache::forgetEducationTypes();
    }

    public function deleted(EducationType $type): void
    {
        $this->saved($type);
    }
}
