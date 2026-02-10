<?php

namespace App\Services\Orders;

use App\Models\Order;

class OrderCrudPipelineService
{
    /**
     * @param  array{main?:array, dynamic?:array}  $validationRules
     * @param  callable(array):void  $validate
     * @param  callable():array  $prepareDefaultBladeData
     * @param  callable():array  $prepareBusinessTripBladeData
     * @param  callable():array  $prepareVacationBladeData
     * @param  callable(array):array{0:array,1:string}  $resolveVacancyData
     * @return array{attributes:array,personnel_ids:array,component_ids:array,vacancy_list:array,message:string}
     */
    public function validateAndPrepare(
        string $selectedBlade,
        array $validationRules,
        callable $validate,
        callable $prepareDefaultBladeData,
        callable $prepareBusinessTripBladeData,
        callable $prepareVacationBladeData,
        callable $resolveVacancyData
    ): array {
        $validate($this->mergeValidationRules(
            $validationRules['main'] ?? [],
            $validationRules['dynamic'] ?? []
        ));

        $bladeData = match ($selectedBlade) {
            Order::BLADE_DEFAULT => $prepareDefaultBladeData(),
            Order::BLADE_BUSINESS_TRIP => $prepareBusinessTripBladeData(),
            default => $prepareVacationBladeData(),
        };

        [$vacancyList, $message] = $resolveVacancyData($bladeData);

        return [
            'attributes' => $bladeData['attributes'] ?? [],
            'personnel_ids' => $bladeData['personnel_ids'] ?? [],
            'component_ids' => $bladeData['component_ids'] ?? [],
            'vacancy_list' => $vacancyList,
            'message' => $message,
        ];
    }

    private function mergeValidationRules(array ...$buckets): array
    {
        $merged = [];

        foreach ($buckets as $bucket) {
            foreach ($bucket as $key => $rule) {
                if ($rule === '' || $rule === null) {
                    continue;
                }

                $merged[$key] = $rule;
            }
        }

        return $merged;
    }
}
