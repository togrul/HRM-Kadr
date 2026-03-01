<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Eloquent;

use App\Models\Order;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRepository;

class EloquentOrderTemplateRepository implements OrderTemplateRepository
{
    public function existsById(int $id): bool
    {
        return Order::query()->whereKey($id)->exists();
    }

    public function createWithId(int $id, array $attributes): Order
    {
        $template = new Order;
        $template->fill($attributes);
        $template->id = $id;
        $template->save();

        return $template->fresh();
    }

    public function hasDependencies(Order $template): bool
    {
        return $template->orderLogs()->exists() || $template->types()->exists();
    }

    public function update(Order $template, array $attributes): Order
    {
        $template->fill($attributes);
        $template->save();

        return $template->fresh();
    }
}

