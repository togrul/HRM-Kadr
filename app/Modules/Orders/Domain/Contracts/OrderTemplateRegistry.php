<?php

namespace App\Modules\Orders\Domain\Contracts;

use App\Models\OrderTemplateVersion;

interface OrderTemplateRegistry
{
    public function activeVersionForOrderType(int $orderTypeId, bool $fresh = false): ?OrderTemplateVersion;

    public function resolveTemplatePathForOrderType(int $orderTypeId): ?string;

    public function invalidate(int $orderTypeId): void;

    public function invalidateReadiness(): void;

    public function hasTemplateSetForOrderType(?int $orderTypeId): bool;

    public function strictModeEnabled(): bool;
}

