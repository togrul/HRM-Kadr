<?php

namespace App\Services\Orders;

use App\Models\OrderLog;

class OrderComponentPersister
{
    public function sync(OrderLog $orderLog, array $componentIds, bool $isUpdate = false): void
    {
        foreach ($componentIds as $row => $componentId) {
            if (empty($componentId)) {
                continue;
            }

            if ($isUpdate && $this->componentExists($orderLog, (int) $row, (int) $componentId)) {
                continue;
            }

            $orderLog->components()->attach([
                $componentId => ['row_number' => $row],
            ]);
        }
    }

    protected function componentExists(OrderLog $orderLog, int $rowNumber, int $componentId): bool
    {
        return $orderLog->components()
            ->wherePivot('row_number', $rowNumber)
            ->wherePivot('component_id', $componentId)
            ->exists();
    }
}
