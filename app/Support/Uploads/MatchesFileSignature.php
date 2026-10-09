<?php

namespace App\Support\Uploads;

use Closure;
use finfo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Faylın uzantısı ilə real məzmununun (imzasının) uyğun gəlməsini yoxlayır.
 *
 * Laravel-in `mimes` qaydası Livewire müvəqqəti fayllarında Flysystem-in MIME aşkarlayıcısına
 * güvənir; o isə `text/plain` nəticəsini qeyri-müəyyən sayıb tipi fayl adından götürür. Bu
 * səbəbdən `.pdf` adlandırılmış mətn faylı `mimes:pdf` yoxlamasından keçirdi. Bu qayda
 * `finfo`-nu birbaşa faylın özünə tətbiq edir.
 */
final class MatchesFileSignature implements ValidationRule
{
    /** @var array<string, list<string>> uzantı => qəbul edilən real MIME tipləri */
    private const SIGNATURES = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'heic' => ['image/heic', 'image/heif'],
        'doc' => ['application/msword', 'application/CDFV2', 'application/x-ole-storage', 'application/vnd.ms-office'],
        'xls' => ['application/vnd.ms-excel', 'application/CDFV2', 'application/x-ole-storage', 'application/vnd.ms-office'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/CDFV2', 'application/x-ole-storage', 'application/vnd.ms-office'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'odt' => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip'],
        'odp' => ['application/vnd.oasis.opendocument.presentation', 'application/zip'],
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'mov' => ['video/quicktime'],
    ];

    /** finfo-nun heç nə deyə bilmədiyi nəticələr (boş və ya ikili məlumat) — qərar `mimes`-ə qalır. */
    private const INCONCLUSIVE = ['application/octet-stream', 'application/x-empty', 'inode/x-empty'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $extension = strtolower((string) $value->getClientOriginalExtension());
        $allowed = self::SIGNATURES[$extension] ?? null;
        $path = $value->getRealPath();

        if ($allowed === null || ! is_string($path) || $path === '' || ! is_file($path)) {
            return;
        }

        $detected = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if ($detected === '' || in_array($detected, self::INCONCLUSIVE, true) || in_array($detected, $allowed, true)) {
            return;
        }

        $fail('validation.mimes')->translate(['values' => $extension]);
    }
}
