<?php

namespace App\Enums;

enum OrderStatusEnum : int
{
    case PENDING = 10;
    case APPROVED = 20;
    case CANCELLED = 30;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
