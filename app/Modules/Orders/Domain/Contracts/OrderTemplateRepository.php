<?php

namespace App\Modules\Orders\Domain\Contracts;

use App\Models\Order;

interface OrderTemplateRepository
{
    public function existsById(int $id): bool;

    /**
     * @param  array<string,mixed>  $attributes
     */
    public function createWithId(int $id, array $attributes): Order;

    public function hasDependencies(Order $template): bool;

    /**
     * @param  array<string,mixed>  $attributes
     */
    public function update(Order $template, array $attributes): Order;
}

