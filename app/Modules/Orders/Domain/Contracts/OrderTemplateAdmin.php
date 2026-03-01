<?php

namespace App\Modules\Orders\Domain\Contracts;

use App\Models\Order;

interface OrderTemplateAdmin
{
    /**
     * @param  array<string,mixed>  $payload
     */
    public function create(array $payload): Order;

    /**
     * @param  array<string,mixed>  $payload
     */
    public function update(Order $template, array $payload): Order;
}

