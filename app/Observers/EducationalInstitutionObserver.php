<?php

namespace App\Observers;

use App\Models\EducationalInstitution;
use App\Support\PersonnelDropdownCache;

class EducationalInstitutionObserver
{
    public function saved(EducationalInstitution $institution): void
    {
        PersonnelDropdownCache::forgetEducationInstitutions();
    }

    public function deleted(EducationalInstitution $institution): void
    {
        $this->saved($institution);
    }
}
