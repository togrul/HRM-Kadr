<?php

namespace App\Helpers;

class UsefulHelpers
{
    public static function compareMultidimensionalArrays($array1, $array2)
    {
        $difference = [];

        if (is_array($array1) && is_array($array2)) {
            foreach ($array1 as $key => $value) {
                if (! array_key_exists($key, $array2)) {
                    $difference[$key] = $value; // Key missing in second array
                } elseif (is_array($value) && is_array($array2[$key])) {
                    $subDifference = self::compareMultidimensionalArrays($value, $array2[$key]);
                    if (! empty($subDifference)) {
                        $difference[$key] = $subDifference; // Difference in nested arrays
                    }
                } else {
                    if ($value !== $array2[$key]) {
                        $difference[$key] = $value; // Different values at the same key
                    }
                }
            }
        } else {
            // Handle non-array comparison or different array structures
            $difference = $array1;
        }

        return $difference;
    }

    public static function searchInsideMultiDimensionalArray($array, $search, $key, $secondKey = null)
    {
        return array_filter($array, function ($v, $k) use ($search, $key, $secondKey) {
            return (! empty($secondKey) ? $v[$key][$secondKey] : $v[$key]) == $search;
        }, ARRAY_FILTER_USE_BOTH);
    }

    public static function convertToMonthNumber($monthName, $locale = 'az')
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

        return $months[$monthName];
    }

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

    public static function modifyArrayToKeyValuePair($array)
    {
        return array_combine(array_keys($array), array_column($array, 'value'));
    }

    public static function getSimilarityPercentage($string1, $string2): float
    {
        similar_text($string1, $string2, $percent);
        return $percent;
    }
}
