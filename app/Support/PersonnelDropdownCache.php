<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PersonnelDropdownCache
{
    public static function forgetAwards(): void
    {
        Cache::forget('personnel:awards');
    }

    public static function forgetPunishments(): void
    {
        Cache::forget('personnel:punishments');
    }

    public static function forgetRanks(): void
    {
        foreach (self::locales() as $locale) {
            Cache::forget("personnel:ranks:{$locale}");
        }
    }

    public static function forgetRankReasons(): void
    {
        Cache::forget('personnel:rank_reasons');
    }

    public static function forgetKinships(): void
    {
        foreach (self::locales() as $locale) {
            Cache::forget("personnel:kinships:{$locale}");
        }
    }

    public static function forgetLanguages(): void
    {
        Cache::forget('personnel:languages');
    }

    public static function forgetScientificDegrees(): void
    {
        Cache::forget('personnel:scientific-degrees');
    }

    public static function forgetEducationInstitutions(): void
    {
        Cache::forget('personnel:education:institutions:primary');
        Cache::forget('personnel:education:institutions:extra');
    }

    public static function forgetEducationForms(): void
    {
        foreach (self::locales() as $locale) {
            Cache::forget("personnel:education:forms:primary:{$locale}");
            Cache::forget("personnel:education:forms:extra:{$locale}");
        }
    }

    public static function forgetEducationTypes(): void
    {
        Cache::forget('personnel:education:types');
    }

    public static function forgetEducationDocumentTypes(): void
    {
        Cache::forget('personnel:education:document_types');
        Cache::forget('personnel:step8:doc-types');
    }

    public static function forgetEducationDegrees(): void
    {
        foreach (self::locales() as $locale) {
            Cache::forget("personnel:education_degree:{$locale}");
        }
    }

    public static function forgetWorkNorms(): void
    {
        foreach (self::locales() as $locale) {
            Cache::forget("personnel:work_norms:{$locale}");
        }
    }

    public static function forgetSocialOrigins(): void
    {
        Cache::forget('personnel:social_origin');
    }

    public static function forgetDisabilities(): void
    {
        Cache::forget('personnel:disabilities');
    }

    public static function forgetStructures(): void
    {
        Cache::forget('personnel:structures');
    }

    public static function forgetPositions(): void
    {
        Cache::forget('personnel:positions');
        Cache::forget('personnel:positions:list');
    }

    public static function forgetCountries(): void
    {
        $suffixes = [
            'current',
            'previous',
            'document-nationality',
            'document-born-country',
        ];

        foreach (self::locales() as $locale) {
            foreach ($suffixes as $suffix) {
                Cache::forget("personnel:country:{$suffix}:{$locale}");
            }
        }
    }

    private static function locales(): array
    {
        $locales = [
            config('app.locale'),
            config('app.fallback_locale'),
        ];

        return array_values(array_filter(array_unique($locales)));
    }
}
