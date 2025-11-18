<?php

namespace App\Observers;

use App\Models\EducationDegree;
use App\Observers\Concerns\FlushesCallPersonnelCache;

class EducationDegreeObserver
{
    use FlushesCallPersonnelCache;

    public function saved(EducationDegree $degree): void
    {
        $this->forgetLocaleAwareCache('education_degrees');
    }

    public function deleted(EducationDegree $degree): void
    {
        $this->saved($degree);
    }
}
