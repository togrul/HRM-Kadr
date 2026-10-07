<?php

namespace App\Support;

/**
 * Seniority band of a position, used only to order lists (senior posts first).
 * Never shown next to a position; HR adjusts it in Admin → Positions when the
 * name-based guess is wrong.
 */
final class PositionLevel
{
    public const LEVELS = [1, 2, 3, 4, 5, 6, 7];

    /** A position nobody has classified sorts after every known level. */
    public const UNKNOWN = 99;

    /**
     * Name fragment => level, checked in order: the first hit wins, so the more
     * specific phrases ("müavin", "müşavir") come before "direktor".
     *
     * @var array<string, int>
     */
    private const RULES = [
        'müavin' => 2,
        'müşavir' => 3,
        'direktor' => 1,
        'rəis' => 4,
        'müdir' => 4,
        'rəhbər' => 4,
        'baş mühəndis' => 3,
        'aparıcı' => 5,
        'böyük' => 5,
        'menecer' => 5,
        'baş ' => 5,
        'sürücü' => 7,
        'xadimə' => 7,
        'fəhlə' => 7,
        'mühafizəçi' => 7,
        'kuryer' => 7,
    ];

    /** Everyone else is a specialist (mühasib, hüquqşünas, mühəndis, mütəxəssis…). */
    private const DEFAULT = 6;

    public static function guess(string $positionName): int
    {
        $name = mb_strtolower($positionName);

        foreach (self::RULES as $fragment => $level) {
            if (str_contains($name, $fragment)) {
                return $level;
            }
        }

        return self::DEFAULT;
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (int $level): array => ['id' => $level, 'label' => __('admin::references.position_levels.'.$level)],
            self::LEVELS,
        );
    }
}
