<?php

namespace App\Livewire\Traits;

use Carbon\Carbon;

trait SelectListTrait
{
    protected function modifyArray($array, $_castedDates = null)
    {
        $filteredArray = array_filter($array, function ($key) {

            return stripos($key, '_id') !== false;

        }, ARRAY_FILTER_USE_KEY);

        foreach ($filteredArray as $key => $value) {
            unset($array[$key]);
            if (is_array($value)) {
                $array[$key] = $value['id'] ?? null;
            } else {
                $array[$key] = $value;
            }
        }

        if (! empty($_castedDates)) {
            foreach ($_castedDates as $_dates) {
                if (! empty($array[$_dates])) {
                    $array[$_dates] = Carbon::parse($array[$_dates])->format('Y-m-d');
                } else {
                    $array[$_dates] = null;
                }
            }
        }

        return $array;
    }
}
