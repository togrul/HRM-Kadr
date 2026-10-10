<?php

namespace App\Support\Uploads;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Yüklənmiş faylları təhlükəsiz qaytarmaq üçün vahid köməkçi.
 *
 * - Brauzerdə (inline) yalnız PDF və raster şəkillər (png/jpeg/webp) açılır; qalan hər şey
 *   (HTML, SVG, XML, ofis sənədləri…) həmişə endirmə (attachment) kimi göndərilir.
 * - Hər cavaba `X-Content-Type-Options: nosniff` və `Content-Security-Policy: sandbox`
 *   əlavə olunur ki, saxlanılan fayl eyni domendə skript kimi icra oluna bilməsin.
 *   İstisna: inline PDF — Chromium-un daxili PDF görüntüləyicisi sandbox sənəddə açılmır,
 *   ona görə ona skriptləri tam bağlayan `default-src 'none'` siyasəti verilir.
 */
final class SecureFileResponse
{
    /** @var array<string, string> inline icazəli uzantı => məcburi Content-Type */
    public const INLINE_TYPES = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
    ];

    public static function canInline(string $path): bool
    {
        return array_key_exists(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::INLINE_TYPES);
    }

    /**
     * Diskdəki faylı qaytarır. `$inline` yalnız icazəli tiplərdə nəzərə alınır.
     *
     * @param  array<string, string>  $headers
     */
    public static function fromDisk(string $disk, string $path, ?string $displayName = null, bool $inline = false, array $headers = []): StreamedResponse
    {
        $name = self::safeName($displayName ?: basename($path), $path);
        $inline = $inline && self::canInline($path);

        if ($inline) {
            $headers['Content-Type'] = self::INLINE_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))];
        }

        $response = Storage::disk($disk)->response($path, $name, $headers, $inline ? 'inline' : 'attachment');

        return self::harden($response);
    }

    /**
     * Generasiya olunan sənəd üçün veb kökündən kənarda (storage/app/tmp) təsadüfi adlı yol.
     * İstifadəçi adı fayl sistemi yoluna heç vaxt düşmür.
     */
    public static function temporaryPath(string $extension): string
    {
        $directory = storage_path('app/tmp');
        File::ensureDirectoryExists($directory);

        return $directory.DIRECTORY_SEPARATOR.Str::uuid()->toString().'.'.ltrim($extension, '.');
    }

    /**
     * Müvəqqəti (generasiya olunmuş) faylı endirmə kimi göndərir və göndərildikdən sonra silir.
     */
    public static function temporaryDownload(string $absolutePath, string $displayName): BinaryFileResponse
    {
        $response = response()
            ->download($absolutePath, self::safeName($displayName, $absolutePath))
            ->deleteFileAfterSend(true);

        return self::harden($response);
    }

    /**
     * @template T of Response
     *
     * @param  T  $response
     * @return T
     */
    public static function harden(Response $response): Response
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        $isInlinePdf = str_starts_with((string) $response->headers->get('Content-Type'), 'application/pdf')
            && ! str_starts_with(strtolower((string) $response->headers->get('Content-Disposition')), 'attachment');

        $response->headers->set(
            'Content-Security-Policy',
            $isInlinePdf ? "default-src 'none'; style-src 'unsafe-inline'; img-src data:; frame-ancestors 'self'" : 'sandbox'
        );

        return $response;
    }

    /**
     * İstifadəçinin gördüyü fayl adını təmizləyir: yol ayırıcıları, idarəetmə simvolları və
     * fayl sistemi üçün təhlükəli simvollar atılır; uzantı mənbə faylından götürülür.
     */
    public static function safeName(string $displayName, ?string $sourcePath = null): string
    {
        $extension = strtolower(pathinfo((string) $sourcePath, PATHINFO_EXTENSION));
        $base = basename(str_replace('\\', '/', $displayName));

        if ($extension !== '' && str_ends_with(strtolower($base), '.'.$extension)) {
            $base = mb_substr($base, 0, mb_strlen($base) - mb_strlen($extension) - 1);
        }

        $base = (string) preg_replace('/[\x00-\x1F\x7F<>:"\/\\\\|?*]+/u', ' ', $base);
        $base = trim((string) preg_replace('/\s+/u', ' ', $base), " .\t");
        $base = Str::limit($base, 150, '');

        if ($base === '') {
            $base = 'fayl';
        }

        return $extension !== '' ? $base.'.'.$extension : $base;
    }
}
