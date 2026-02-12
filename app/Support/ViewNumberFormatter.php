<?php

namespace App\Support;

class ViewNumberFormatter
{
    public static function decimal($value, int $precision = 2)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (! is_numeric($value)) {
            return $value;
        }

        $formatted = number_format(round((float) $value, $precision), $precision, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}

