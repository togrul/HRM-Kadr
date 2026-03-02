<?php

namespace App\Modules\Orders\Application\UseCases\Templates;

use App\Models\Order;
use App\Models\OrderType;
use App\Modules\Orders\Domain\Contracts\OrderTemplateReadRepository;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use Illuminate\Support\Collection;

class SetTypeReadUseCase
{
    public function __construct(
        private readonly OrderTemplateReadRepository $templateReadRepository,
        private readonly OrderTypeStatusLookupReadRepository $orderTypeStatusLookup,
    ) {}

    public function loadTemplateOrFail(int $templateId): Order
    {
        return $this->templateReadRepository->findTemplateOrFail($templateId);
    }

    public function orderTypesWithActiveVersion(int $templateId): Collection
    {
        return $this->templateReadRepository->orderTypesWithActiveVersion($templateId);
    }

    public function resolveOwnedType(int $templateId, int $typeId): ?OrderType
    {
        $type = $this->orderTypeStatusLookup->findOrderType($typeId);
        if (! $type) {
            return null;
        }

        return (int) $type->order_id === $templateId ? $type : null;
    }

    public function orderTypeForUiConfig(int $orderTypeId): ?OrderType
    {
        return $this->orderTypeStatusLookup->findOrderType($orderTypeId, [
            'templateSet:id,order_type_id',
        ]);
    }

    public function orderTypeForMetadataBootstrap(int $orderTypeId): ?OrderType
    {
        return $this->orderTypeStatusLookup->findOrderType($orderTypeId, [
            'templateSet.activeVersion',
        ]);
    }
}

