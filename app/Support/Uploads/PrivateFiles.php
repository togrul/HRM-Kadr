<?php

namespace App\Support\Uploads;

use App\Models\Personnel;
use App\Models\User;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Support\Facades\Storage;

/**
 * Həssas yükləmələrin (məzuniyyət/xəstəlik sənədləri, sertifikatlar, portfel əlavələri,
 * onboarding sənədləri, işçi fotoları) saxlanma qaydası.
 *
 * Yeni fayllar veb ilə birbaşa açılmayan `local` diskə (storage/app) yazılır və yalnız
 * sahib qeydin icazəsini yoxlayan route-lar vasitəsilə verilir. `files:privatize` əmri
 * köhnə faylları `public` diskdən köçürənə qədər oxuma həm `local`, həm `public`-ə baxır.
 */
final class PrivateFiles
{
    public const DISK = 'local';

    public const LEGACY_DISK = 'public';

    /**
     * Faylın hazırda olduğu disk: əvvəlcə qeyddəki disk, sonra `local`, sonra köhnə `public`.
     */
    public static function locate(?string $path, ?string $recordedDisk = null): ?string
    {
        $path = (string) $path;

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        $candidates = array_values(array_unique(array_filter([$recordedDisk, self::DISK, self::LEGACY_DISK])));

        foreach ($candidates as $disk) {
            if (in_array($disk, [self::DISK, self::LEGACY_DISK], true) && Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    /**
     * Faylı (hansı diskdə olursa olsun) silir.
     */
    public static function delete(?string $path, ?string $recordedDisk = null): void
    {
        $disk = self::locate($path, $recordedDisk);

        if ($disk !== null) {
            Storage::disk($disk)->delete((string) $path);
        }
    }

    /**
     * İstifadəçinin öz əməkdaş kartıdırmı (yalnız admin tərəfindən yaradılmış açıq bağ ilə).
     */
    public static function isOwnPersonnel(?User $user, Personnel|int|null $personnel): bool
    {
        if ($user === null || $personnel === null) {
            return false;
        }

        $linkedId = app(UserPersonnelLinkResolver::class)->resolve($user);
        $personnelId = $personnel instanceof Personnel ? (int) $personnel->getKey() : (int) $personnel;

        return $linkedId !== null && $personnelId > 0 && $linkedId === $personnelId;
    }

    /**
     * Tabel nömrəsi istifadəçinin öz əməkdaş kartına aiddirmi.
     */
    public static function isOwnTabelNo(?User $user, ?string $tabelNo): bool
    {
        if ($user === null || blank($tabelNo)) {
            return false;
        }

        $linkedId = app(UserPersonnelLinkResolver::class)->resolve($user);

        return $linkedId !== null && Personnel::query()
            ->whereKey($linkedId)
            ->where('tabel_no', $tabelNo)
            ->exists();
    }
}
