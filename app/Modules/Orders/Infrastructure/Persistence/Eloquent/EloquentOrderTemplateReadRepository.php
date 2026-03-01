<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Eloquent;

use App\Models\Order;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Modules\Orders\Domain\Contracts\OrderTemplateReadRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentOrderTemplateReadRepository implements OrderTemplateReadRepository
{
    public function templateOptions(): array
    {
        return Order::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (Order $order): array => [
                'id' => (int) $order->id,
                'label' => (string) $order->name,
            ])
            ->values()
            ->all();
    }

    public function orderTypeOptionsForTemplate(int $templateId): array
    {
        if ($templateId <= 0) {
            return [];
        }

        return OrderType::query()
            ->where('order_id', $templateId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (OrderType $type): array => [
                'id' => (int) $type->id,
                'label' => (string) $type->name,
            ])
            ->values()
            ->all();
    }

    public function versionOptionsForOrderType(int $orderTypeId): array
    {
        if ($orderTypeId <= 0) {
            return [];
        }

        $setId = OrderTemplateSet::query()
            ->where('order_type_id', $orderTypeId)
            ->value('id');

        if (! $setId) {
            return [];
        }

        return OrderTemplateVersion::query()
            ->where('order_template_set_id', (int) $setId)
            ->orderByDesc('version_no')
            ->orderByDesc('id')
            ->get(['id', 'version_no', 'status', 'is_active'])
            ->map(fn (OrderTemplateVersion $version): array => [
                'id' => (int) $version->id,
                'label' => sprintf(
                    'v%s (%s%s)',
                    (int) $version->version_no,
                    (string) $version->status,
                    $version->is_active ? ', active' : ''
                ),
            ])
            ->values()
            ->all();
    }

    public function findTemplateOrFail(int $templateId): Order
    {
        return Order::query()->findOrFail($templateId);
    }

    public function orderTypesWithActiveVersion(int $templateId): Collection
    {
        return OrderType::query()
            ->where('order_id', $templateId)
            ->with('templateSet.activeVersion')
            ->get();
    }

    public function paginateTemplates(string $status, int $perPage = 24): LengthAwarePaginator
    {
        return Order::query()
            ->with('category')
            ->when($status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->paginate($perPage);
    }

    public function findTemplateWithTrashed(int $templateId): ?Order
    {
        return Order::withTrashed()->where('id', $templateId)->first();
    }
}

