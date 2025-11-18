<?php

namespace App\Observers\Concerns;

use App\Services\CallPersonnelInfo;

trait FlushesCallPersonnelCache
{
    protected function forgetCallPersonnelCache(string $prefix, $suffix = null): void
    {
        CallPersonnelInfo::forgetCacheKey($prefix, $suffix);
    }

    protected function forgetLocaleAwareCache(string $prefix): void
    {
        foreach ($this->localesToFlush() as $locale) {
            $this->forgetCallPersonnelCache($prefix, $locale);
        }
    }

    private function localesToFlush(): array
    {
        $locales = [
            config('app.locale'),
            config('app.fallback_locale'),
        ];

        return array_values(array_filter(array_unique($locales)));
    }
}
