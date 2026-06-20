<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Eloquent;

use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentOrderTypeStatusLookupReadRepository implements OrderTypeStatusLookupReadRepository
{
    public function orderTypes(?int $orderId = null, ?string $search = null): Collection
    {
        $normalizedSearch = trim((string) $search);

        return OrderType::query()
            ->when($orderId, fn (Builder $query) => $query->where('order_id', $orderId))
            ->when($normalizedSearch !== '', fn (Builder $query) => $query->where('name', 'LIKE', "%{$normalizedSearch}%"))
            ->orderBy('name')
            ->get();
    }

    public function orderTypeNameById(int $orderTypeId): ?string
    {
        if ($orderTypeId <= 0) {
            return null;
        }

        return OrderType::query()
            ->whereKey($orderTypeId)
            ->value('name');
    }

    public function findOrderType(int $orderTypeId, array $relations = []): ?OrderType
    {
        if ($orderTypeId <= 0) {
            return null;
        }

        return OrderType::query()
            ->with($relations)
            ->find($orderTypeId);
    }

    public function localizedStatuses(string $locale): Collection
    {
        return OrderStatus::query()
            ->where('locale', $locale)
            ->get();
    }
}
