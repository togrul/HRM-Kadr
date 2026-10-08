<?php

namespace App\Support\Language;

/**
 * Ata adının "oğlu" / "qızı" şəkilçisi.
 *
 * Bazada ata adı çılpaq saxlanılır ("Hikmət"), şəkilçi isə göstərilərkən cinsə görə
 * əlavə olunur. Köhnə və idxal olunmuş qeydlərdə ata adı artıq "Hikmət oğlu" kimi
 * yazılıb; belə hallarda şəkilçi ikinci dəfə əlavə olunmamalıdır ("Hikmət oğlu oğlu").
 * Müqayisə böyük/kiçik hərfə və latın transliterasiyasına (OGLU, QIZI) həssas deyil.
 */
final class AzerbaijaniPatronymic
{
    public const MALE_SUFFIX = 'oğlu';

    public const FEMALE_SUFFIX = 'qızı';

    /** Qatlanmış (fold) formada tanınan şəkilçilər. */
    private const KNOWN_SUFFIXES = ['oglu', 'qizi', 'ogly', 'qyzy'];

    /** Cins kodu 2 qadın, qalanı kişi (Personnel::gender ilə eyni). */
    public static function suffixFor(mixed $gender): string
    {
        return (int) $gender === 2 ? self::FEMALE_SUFFIX : self::MALE_SUFFIX;
    }

    /**
     * Mətnin son sözü oğlu/qızı şəkilçisidirmi.
     */
    public static function endsWithSuffix(?string $value): bool
    {
        $tokens = self::tokens($value);

        return $tokens !== [] && in_array(self::fold(end($tokens)), self::KNOWN_SUFFIXES, true);
    }

    /**
     * Ata adının sonundakı şəkilçini atır: "Hikmət oğlu" → "Hikmət". Yalnız şəkilçidən
     * ibarət dəyər olduğu kimi qalır ki, boş ata adı yaranmasın.
     */
    public static function strip(string $patronymic): string
    {
        $tokens = self::tokens($patronymic);

        if (count($tokens) < 2 || ! self::endsWithSuffix($patronymic)) {
            return trim($patronymic);
        }

        array_pop($tokens);

        return implode(' ', $tokens);
    }

    /**
     * Tam ada cinsə uyğun şəkilçini əlavə edir, artıq varsa təkrarlamır.
     */
    public static function appendTo(string $fullName, mixed $gender): string
    {
        $fullName = trim($fullName);

        if ($fullName === '' || self::endsWithSuffix($fullName)) {
            return $fullName;
        }

        return $fullName.' '.self::suffixFor($gender);
    }

    /**
     * @return list<string>
     */
    private static function tokens(?string $value): array
    {
        $parts = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);

        return $parts === false ? [] : $parts;
    }

    /**
     * Azərbaycan hərflərini latın əsasına endirir: "OĞLU", "oğlu", "Oglu" → "oglu";
     * "QIZI", "qızı" → "qizi".
     */
    private static function fold(string $word): string
    {
        $word = strtr($word, ['İ' => 'i', 'I' => 'i', 'Ğ' => 'g', 'ğ' => 'g', 'ı' => 'i']);

        return str_replace("\u{0307}", '', mb_strtolower($word));
    }
}
