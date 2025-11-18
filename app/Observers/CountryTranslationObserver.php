<?php

namespace App\Observers;

use App\Models\CountryTranslation;
use App\Observers\Concerns\FlushesCallPersonnelCache;
use Illuminate\Support\Facades\Cache;

class CountryTranslationObserver
{
    use FlushesCallPersonnelCache;

    public function saved(CountryTranslation $translation): void
    {
        $this->forgetCallPersonnelCache('nationalities');
        Cache::forget('nationality:azerbaijan');
    }

    public function deleted(CountryTranslation $translation): void
    {
        $this->saved($translation);
    }
}
