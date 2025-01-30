<?php

namespace App\Enums;

enum RankCategoryEnum: int
{
    case CAVUS = 10;
    case GIZIR = 20;
    case ZABIT = 30;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
