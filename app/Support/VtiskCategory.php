<?php

namespace App\Support;

/**
 * Vəzifənin Vahid Tarif-İxtisas Sorğu Kitabçasındakı (VTİSK) kateqoriyası.
 *
 * Qulluqçu kitabçası (ƏƏSMN Kollegiyası, 15.11.2005, 24-2) vəzifələri üç bölməyə ayırır —
 * rəhbərlər, mütəxəssislər, texniki icraçılar; fəhlə peşələri ayrıca kitabçalardadır.
 * ƏM m.114.3 "b" (332-VIIQD, 16.01.2026-dan): rəhbər və mütəxəssis kateqoriyasına aid işçilərə
 * əsas məzuniyyət 30 təqvim günüdür — bu, «Məzuniyyət normaları»ndakı qanuni sətirdir.
 */
final class VtiskCategory
{
    public const MANAGER = 'rehber';

    public const SPECIALIST = 'mutexessis';

    public const TECHNICAL = 'texniki_icraci';

    public const WORKER = 'fehle';

    public const ALL = [self::MANAGER, self::SPECIALIST, self::TECHNICAL, self::WORKER];

    /**
     * Mövcud vəzifələr üçün ilkin təxmin — sıralama səviyyəsindən (PositionLevel). 1–4 rəhbər
     * heyət, 5–6 mütəxəssis; köməkçi heyət (7) təxmin edilmir: sürücü fəhlədir, kargüzar texniki
     * icraçıdır və 30 gün yalnız səhv seçimlə verilə bilər. HR Admin → Vəzifələr-də düzəldir.
     */
    public static function guessFromLevel(?int $level): ?string
    {
        return match (true) {
            $level !== null && $level >= 1 && $level <= 4 => self::MANAGER,
            $level === 5 || $level === 6 => self::SPECIALIST,
            default => null,
        };
    }

    public static function label(?string $category): string
    {
        return $category !== null && in_array($category, self::ALL, true)
            ? __('admin::references.vtisk_categories.'.$category)
            : '—';
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (string $category): array => ['id' => $category, 'label' => self::label($category)],
            self::ALL,
        );
    }
}
