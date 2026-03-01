<?php

namespace App\Modules\Orders\Application\UseCases\Templates;

use App\Models\Order;
use App\Models\OrderType;

class ManageSetTypeOrderTypesUseCase
{
    public function addType(Order $template, array $payload): OrderType
    {
        /** @var OrderType $type */
        $type = $template->types()->create($payload);

        return $type;
    }

    public function removeType(Order $template, int $typeId): bool
    {
        $type = $template->types()->whereKey($typeId)->first();

        if (! $type) {
            return false;
        }

        $type->delete();

        return true;
    }

    public function updateType(Order $template, int $typeId, array $payload): bool
    {
        $type = $template->types()->whereKey($typeId)->first();

        if (! $type) {
            return false;
        }

        $type->update($payload);

        return true;
    }
}
