<?php

namespace App\Observers;

use App\Models\CountryTranslation;
use App\Observers\Concerns\FlushesCallPersonnelCache;
use App\Support\PersonnelDropdownCache;
use Illuminate\Support\Facades\Cache;

class CountryTranslationObserver
{
    use FlushesCallPersonnelCache;

    public function saved(CountryTranslation $translation): void
    {
        $this->forgetCallPersonnelCache('nationalities');
        Cache::forget('nationality:azerbaijan');
        PersonnelDropdownCache::forgetCountries();
    }

    public function deleted(CountryTranslation $translation): void
    {
        $this->saved($translation);
    }
}
