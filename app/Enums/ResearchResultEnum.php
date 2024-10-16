<?php

namespace App\Enums;

enum ResearchResultEnum: string
{
    case Positive = 'müsbət';
    case Negative = 'mənfi';
    case Informed = 'məlumatlı';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
