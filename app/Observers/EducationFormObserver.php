<?php

namespace App\Observers;

use App\Models\EducationForm;
use App\Support\PersonnelDropdownCache;

class EducationFormObserver
{
    public function saved(EducationForm $form): void
    {
        PersonnelDropdownCache::forgetEducationForms();
    }

    public function deleted(EducationForm $form): void
    {
        $this->saved($form);
    }
}
