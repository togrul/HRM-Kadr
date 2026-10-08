<?php

namespace App\Support\Uploads;

/**
 * Yüklənən faylların vahid validasiya qaydaları.
 *
 * `mimes` qaydası faylın adına yox, məzmununa (MIME sniffing) baxır — `.pdf` adlandırılmış
 * mətn faylı və ya HTML/SVG kimi brauzerdə icra oluna bilən fayllar rədd edilir. Fayllar
 * public diskdə saxlandığı üçün bu, eyni domendə saxlanılan XSS-in qarşısını alır.
 */
final class UploadRules
{
    /** Ofis sənədləri və şəkillər (əmr, ərizə, siyasət, arayış). */
    public const DOCUMENT_MIMES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'jpg', 'jpeg', 'png', 'webp', 'heic'];

    /** Tədris materialları: sənədlərə əlavə olaraq təqdimat və video. */
    public const LEARNING_MIMES = [...self::DOCUMENT_MIMES, 'ppt', 'pptx', 'odp', 'mp4', 'webm', 'mov'];

    /**
     * @return list<string|MatchesFileSignature>
     */
    public static function document(bool $required = true, int $maxKb = 10240): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'max:'.$maxKb,
            'mimes:'.implode(',', self::DOCUMENT_MIMES),
            new MatchesFileSignature,
        ];
    }

    /**
     * @return list<string|MatchesFileSignature>
     */
    public static function learningAsset(bool $required = false, int $maxKb = 20480): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'max:'.$maxKb,
            'mimes:'.implode(',', self::LEARNING_MIMES),
            new MatchesFileSignature,
        ];
    }
}
