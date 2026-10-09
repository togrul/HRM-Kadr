<?php

namespace App\Modules\Personnel\Support;

use App\Support\Language\AzerbaijaniPatronymic;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Şəxsi məlumat sahələrinin formatı və normallaşdırılması.
 *
 * Forma, idxal və digər giriş yolları eyni qaydaları buradan oxuyur ki, FİN,
 * telefon və tabel nömrəsi bir yerdə bir cür, başqa yerdə başqa cür yoxlanmasın.
 */
final class PersonnelFieldRules
{
    /** FİN: 7 simvol, yalnız böyük latın hərfləri və rəqəmlər. */
    public const PIN_PATTERN = '/^[A-Z0-9]{7}$/';

    /** Normallaşdırılmış telefon: istəyə bağlı "+" və 9–15 rəqəm. */
    public const PHONE_PATTERN = '/^\+?[0-9]{9,15}$/';

    /**
     * Tabel nömrəsi hərf və ya rəqəmlə başlayır; içində tire, nöqtə, slash və alt
     * xətt ola bilər. Mövcud nömrələr ("TB-001", "DMX-26-000002") hərf-rəqəmlidir,
     * ona görə yalnız rəqəm tələb etmək olmaz — amma "-5" kimi mənfi və boş görünən
     * dəyərlər rədd edilir.
     */
    public const TABEL_NO_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9\-\/._]*$/';

    /** Yalnız sıfırlardan ibarət tabel nömrəsi (0, 000) etibarsızdır. */
    public const TABEL_NO_ZERO_PATTERN = '/^0+$/';

    /** ƏM m.42: əmək müqaviləsi 15 yaşdan bağlana bilər. */
    public const MINIMUM_HIRING_AGE = 15;

    /** Doğum tarixi üçün ağlabatan yuxarı hədd. */
    public const MAXIMUM_AGE = 100;

    /**
     * ƏM m.51: sınaq müddəti üç aydan çox ola bilməz. Vahidə görə ən böyük say.
     *
     * @var array<string, int>
     */
    public const PROBATION_LIMITS = [
        'day' => 92,
        'week' => 13,
        'month' => 3,
    ];

    public static function normalizePin(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return mb_strtoupper(preg_replace('/\s+/u', '', $value) ?? $value);
    }

    /**
     * Boşluq, tire, nöqtə və mötərizələri atır; əvvəldəki "+" saxlanılır.
     * Hərf kimi başqa simvollar qalır ki, format qaydası onları rədd etsin.
     */
    public static function normalizePhone(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $hasPlus = str_starts_with($value, '+');
        $stripped = preg_replace('/[\s\-().+]/u', '', $value) ?? $value;

        return ($hasPlus ? '+' : '').$stripped;
    }

    public static function normalizeEmail(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public static function normalizeTabelNo(mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }

    public static function normalizePatronymic(mixed $value): mixed
    {
        return is_string($value) ? AzerbaijaniPatronymic::strip($value) : $value;
    }

    /**
     * Doğum tarixindən verilmiş tarixə qədər tam il sayı; tarixlərdən biri oxunmursa null.
     */
    public static function ageOn(mixed $birthdate, mixed $onDate): ?int
    {
        $birth = self::parseDate($birthdate);
        $on = self::parseDate($onDate) ?? CarbonImmutable::today();

        if ($birth === null) {
            return null;
        }

        return (int) floor($birth->diffInYears($on, false));
    }

    public static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
