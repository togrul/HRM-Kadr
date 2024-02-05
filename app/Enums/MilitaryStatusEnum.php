<?php

namespace App\Enums;

enum MilitaryStatusEnum : string
{
    case Useful = 'yararlı';
    case Useless = 'yararsız';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
