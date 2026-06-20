<?php

namespace App\Modules\Orders\Domain\Contracts;

use App\Models\OrderType;
use Illuminate\Support\Collection;

interface OrderTypeStatusLookupReadRepository
{
    public function orderTypes(?int $orderId = null, ?string $search = null): Collection;

    public function orderTypeNameById(int $orderTypeId): ?string;

    /**
     * @param  array<int|string,mixed>  $relations
     */
    public function findOrderType(int $orderTypeId, array $relations = []): ?OrderType;

    public function localizedStatuses(string $locale): Collection;
}
