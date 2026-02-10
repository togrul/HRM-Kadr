<?php

namespace App\Services\Orders;

class OrderAttributeMappingService
{
    /**
     * @param  array<int,array<string,mixed>>  $componentForms
     * @param  callable(string,mixed,?int):mixed  $attributeValueResolver
     * @return array<int,array<string,mixed>>
     */
    public function mapComponentAttributes(array $componentForms, callable $attributeValueResolver): array
    {
        $mapped = [];

        foreach ($componentForms as $row => $component) {
            foreach ($component as $key => $value) {
                $targetKey = match ($key) {
                    'rank_id' => '$rank',
                    'personnel_id' => '$fullname',
                    'structure_main_id' => '$structure_main',
                    'structure_id' => '$structure',
                    'position_id' => '$position',
                    'component_id', 'row' => $key,
                    default => '$'.$key,
                };

                $mapped[$row][$targetKey] = $attributeValueResolver($key, $value, $row);
            }
        }

        return $mapped;
    }
}
