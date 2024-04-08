<?php

namespace App\Enums;

enum StructureEnum : int
{
    case ESAS = 0;
    case IDARE = 1;
    case SOBE = 2;
    case BOLME = 3;

    case TABOR = 12;

    case BOLUK = 13;
    case TAQIM = 14;

    case MANQA = 15;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
