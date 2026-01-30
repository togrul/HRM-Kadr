<?php

namespace App\Observers;

use App\Models\Language;
use App\Support\PersonnelDropdownCache;

class LanguageObserver
{
    public function saved(Language $language): void
    {
        PersonnelDropdownCache::forgetLanguages();
    }

    public function deleted(Language $language): void
    {
        $this->saved($language);
    }
}
