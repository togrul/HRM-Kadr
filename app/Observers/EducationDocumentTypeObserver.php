<?php

namespace App\Observers;

use App\Models\EducationDocumentType;
use App\Support\PersonnelDropdownCache;

class EducationDocumentTypeObserver
{
    public function saved(EducationDocumentType $type): void
    {
        PersonnelDropdownCache::forgetEducationDocumentTypes();
    }

    public function deleted(EducationDocumentType $type): void
    {
        $this->saved($type);
    }
}
