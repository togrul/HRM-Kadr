<?php

namespace App\Enums;

enum GenderEnum: int
{
    case GENDER_MALE = 1;
    case GENDER_FEMALE = 2;
    public static function genderOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case === self::GENDER_MALE
                ? __('staff::common.fields.man')
                : __('staff::common.fields.woman');
        }
        return $options;
    }
}
