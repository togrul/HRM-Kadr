<?php

namespace App\Enums;

enum AttitudeMilitaryEnum: string
{
    case Hm = 'h/m';
    case Hq = 'h/q';
    case Hv = 'h/v';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
