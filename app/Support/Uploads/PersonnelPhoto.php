<?php

namespace App\Support\Uploads;

/**
 * İşçi fotosunun URL-i: özəl diskdəki faylı icazə yoxlayan route verir. `v` parametri
 * fayl yolundan törəyir — foto dəyişəndə URL də dəyişir, ona görə brauzer keşi təhlükəsizdir.
 */
final class PersonnelPhoto
{
    public static function url(int|string|null $personnelId, ?string $path): ?string
    {
        if (blank($path) || blank($personnelId)) {
            return null;
        }

        return route('personnel.photo', [
            'personnel' => (int) $personnelId,
            'v' => substr(sha1((string) $path), 0, 12),
        ]);
    }
}
