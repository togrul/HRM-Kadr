<?php

namespace App\Helpers;

class UsefulHelpers
{
    public static function monthsList(string $locale): array
    {
        if ($locale == 'az') {
            $months = [
                'yanvar' => 1,
                'fevral' => 2,
                'mart' => 3,
                'aprel' => 4,
                'may' => 5,
                'iyun' => 6,
                'iyul' => 7,
                'avqust' => 8,
                'sentyabr' => 9,
                'oktyabr' => 10,
                'noyabr' => 11,
                'dekabr' => 12,
            ];
        }

        return $months;
    }

    public static function getSimilarityPercentage($string1, $string2): float
    {
        similar_text($string1, $string2, $percent);

        return $percent;
    }
}
