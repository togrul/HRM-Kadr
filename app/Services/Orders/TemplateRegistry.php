<?php

namespace App\Services\Orders;

use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TemplateRegistry
{
    private bool $strictMode;
    private int $cacheMinutes;
    private int $readyCacheMinutes;
    private string $readinessMode;
    /** @var array<int,bool> */
    private array $templateSetExistsMemory = [];

    private ?bool $isReadyCache = null;

    /**
     * @var array<int,\App\Models\OrderTemplateVersion|null>
     */
    private array $activeVersionMemory = [];

    public function __construct()
    {
        $this->strictMode = (bool) config('orders.engine.strict_mode', false);
        $this->cacheMinutes = max(1, (int) config('orders.template_registry.active_version_cache_minutes', 15));
        $this->readyCacheMinutes = max(1, (int) config('orders.template_registry.readiness_cache_minutes', 60));
        $this->readinessMode = (string) config('orders.template_registry.readiness_mode', 'schema_cached');
    }

    public function activeVersionForOrderType(int $orderTypeId, bool $fresh = false): ?OrderTemplateVersion
    {
        if (! $fresh && array_key_exists($orderTypeId, $this->activeVersionMemory)) {
            return $this->activeVersionMemory[$orderTypeId];
        }

        if (! $this->isReady()) {
            $this->activeVersionMemory[$orderTypeId] = null;
            return null;
        }

        try {
            $versionId = $this->resolveActiveVersionId($orderTypeId, $fresh);
            if (! $versionId) {
                $this->activeVersionMemory[$orderTypeId] = null;
                return null;
            }

            $version = $this->versionWithSchemaQuery()->find($versionId);
            if ($version) {
                $this->activeVersionMemory[$orderTypeId] = $version;
                return $version;
            }

            $this->invalidate($orderTypeId);
        } catch (QueryException $exception) {
            if (! $this->isMissingTemplateTableQuery($exception)) {
                throw $exception;
            }

            $this->isReadyCache = false;
            $this->activeVersionMemory[$orderTypeId] = null;

            return null;
        }

        if ($fresh) {
            $this->activeVersionMemory[$orderTypeId] = null;
            return null;
        }

        return $this->activeVersionForOrderType($orderTypeId, true);
    }

    public function resolveTemplatePathForOrderType(int $orderTypeId): ?string
    {
        $version = $this->activeVersionForOrderType($orderTypeId);
        if ($version) {
            return (string) $version->template_path;
        }

        return null;
    }

    public function invalidate(int $orderTypeId): void
    {
        Cache::forget($this->cacheKey($orderTypeId));
        Cache::forget($this->templateSetExistsCacheKey($orderTypeId));
        unset($this->activeVersionMemory[$orderTypeId]);
        unset($this->templateSetExistsMemory[$orderTypeId]);
    }

    public function invalidateReadiness(): void
    {
        Cache::forget($this->readyCacheKey());
        $this->isReadyCache = null;
    }

    public function hasTemplateSetForOrderType(?int $orderTypeId): bool
    {
        $resolvedOrderTypeId = is_numeric($orderTypeId) ? (int) $orderTypeId : 0;
        if ($resolvedOrderTypeId <= 0) {
            return false;
        }

        if (! $this->isReady()) {
            return false;
        }

        if (array_key_exists($resolvedOrderTypeId, $this->templateSetExistsMemory)) {
            return $this->templateSetExistsMemory[$resolvedOrderTypeId];
        }

        $exists = (bool) Cache::remember(
            $this->templateSetExistsCacheKey($resolvedOrderTypeId),
            now()->addMinutes($this->cacheMinutes),
            fn () => OrderTemplateSet::query()->where('order_type_id', $resolvedOrderTypeId)->exists()
        );

        return $this->templateSetExistsMemory[$resolvedOrderTypeId] = $exists;
    }

    public function strictModeEnabled(): bool
    {
        return $this->strictMode;
    }

    private function isReady(): bool
    {
        if ($this->readinessMode === 'assume_ready') {
            return true;
        }

        if ($this->isReadyCache !== null) {
            return $this->isReadyCache;
        }

        if (app()->runningUnitTests()) {
            return $this->isReadyCache = $this->computeReadiness();
        }

        return $this->isReadyCache = (bool) Cache::remember(
            $this->readyCacheKey(),
            now()->addMinutes($this->readyCacheMinutes),
            fn () => $this->computeReadiness()
        );
    }

    private function computeReadiness(): bool
    {
        return Schema::hasTable('order_template_sets')
            && Schema::hasTable('order_template_versions');
    }

    private function isMissingTemplateTableQuery(QueryException $exception): bool
    {
        $message = strtolower((string) $exception->getMessage());

        return str_contains($message, 'order_template_')
            && (
                str_contains($message, "doesn't exist")
                || str_contains($message, 'does not exist')
                || str_contains($message, 'unknown table')
                || str_contains($message, 'base table or view not found')
            );
    }

    private function resolveActiveVersionId(int $orderTypeId, bool $fresh = false): ?int
    {
        if ($fresh) {
            $this->invalidate($orderTypeId);
        }

        return Cache::remember(
            $this->cacheKey($orderTypeId),
            now()->addMinutes($this->cacheMinutes),
            fn () => $this->activeVersionIdQuery($orderTypeId)->value('id')
        );
    }

    private function activeVersionIdQuery(int $orderTypeId): Builder
    {
        return OrderTemplateVersion::query()
            ->where('is_active', true)
            ->whereHas('templateSet', fn (Builder $query) => $query->where('order_type_id', $orderTypeId))
            ->orderByDesc('version_no');
    }

    private function versionWithSchemaQuery(): Builder
    {
        return OrderTemplateVersion::query()->with([
            'templateSet:id,order_type_id,name',
            'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);
    }

    private function cacheKey(int $orderTypeId): string
    {
        return "orders:template_registry:active_version:{$orderTypeId}";
    }

    private function readyCacheKey(): string
    {
        return 'orders:template_registry:is_ready';
    }

    private function templateSetExistsCacheKey(int $orderTypeId): string
    {
        return "orders:template_registry:template_set_exists:{$orderTypeId}";
    }
}
