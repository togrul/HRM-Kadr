<?php

namespace App\Services\Orders;

use App\Helpers\UsefulHelpers;

class VacancyDiffService
{
    public function diff(array $current, array $original): array
    {
        if (empty($original)) {
            return $current;
        }

        return UsefulHelpers::compareMultidimensionalArrays($current, $original);
    }
}
