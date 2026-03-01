<?php

namespace App\Modules\Orders\Domain\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrderTemplateReadRepository
{
    /**
     * @return array<int,array{id:int,label:string}>
     */
    public function templateOptions(): array;

    /**
     * @return array<int,array{id:int,label:string}>
     */
    public function orderTypeOptionsForTemplate(int $templateId): array;

    /**
     * @return array<int,array{id:int,label:string}>
     */
    public function versionOptionsForOrderType(int $orderTypeId): array;

    public function findTemplateOrFail(int $templateId): Order;

    /**
     * @return Collection<int,\App\Models\OrderType>
     */
    public function orderTypesWithActiveVersion(int $templateId): Collection;

    public function paginateTemplates(string $status, int $perPage = 24): LengthAwarePaginator;

    public function findTemplateWithTrashed(int $templateId): ?Order;
}

