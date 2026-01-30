<?php

namespace App\Observers;

use App\Models\EducationDegree;
use App\Observers\Concerns\FlushesCallPersonnelCache;
use App\Support\PersonnelDropdownCache;

class EducationDegreeObserver
{
    use FlushesCallPersonnelCache;

    public function saved(EducationDegree $degree): void
    {
        $this->forgetLocaleAwareCache('education_degrees');
        PersonnelDropdownCache::forgetEducationDegrees();
    }

    public function deleted(EducationDegree $degree): void
    {
        $this->saved($degree);
    }
}
