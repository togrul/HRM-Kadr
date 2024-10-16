<?php

namespace App\Enums;

enum TransportationEnum: string
{
    case CAR = 'with_cars';
    case BUS = 'with_bus';
    case TRAIN = 'with_train';
    case AIRPLANE = 'with_airplane';
    case HELICOPTER = 'with_helicopter';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function list(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->name, self::cases()), // Keys from enum names
            array_map(fn ($case) => $case->value, self::cases()) // Values from enum values
        );
    }
}
