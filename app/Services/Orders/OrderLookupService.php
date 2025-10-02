<?php

namespace App\Services\Orders;

use App\Models\Candidate;
use App\Models\Component;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Structure;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrderLookupService
{
    public function __construct(private readonly StructureService $structureService)
    {
    }

    public function templates(?int $orderId, ?string $search = null): Collection
    {
        return OrderType::query()
            ->when($search, fn (Builder $query) => $query->where('name', 'LIKE', "%{$search}%"))
            ->when($orderId, fn (Builder $query) => $query->where('order_id', $orderId))
            ->orderBy('name')
            ->get();
    }

    public function components(?int $templateId): Collection
    {
        if (! $templateId) {
            return collect();
        }

        return Component::query()
            ->with('orderType')
            ->where('order_type_id', $templateId)
            ->orderBy('name')
            ->get();
    }

    public function personnels(bool $forCandidates, array $excludeIds, ?string $search = null): Collection
    {
        return $forCandidates
            ? $this->candidatePersonnelQuery($excludeIds, $search)->get()
            : $this->activePersonnelQuery($excludeIds, $search)->get();
    }

    public function ranks(): Collection
    {
        return Rank::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    public function mainStructures(): Collection
    {
        return Structure::query()
            ->where('code', 0)
            ->orderBy('id')
            ->get(['id', 'name', 'code']);
    }

    public function structures(?string $search = null): Collection
    {
        return Structure::query()
            ->withRecursive('subs')
            ->accessible()
            ->when($search, fn (Builder $query) => $query->where('name', 'LIKE', "%{$search}%"))
            ->whereNotNull('parent_id')
            ->where('code', '<>', 0)
            ->orderBy('code')
            ->get();
    }

    public function positions(?string $search = null): Collection
    {
        return Position::query()
            ->when($search, fn (Builder $query) => $query->where('name', 'LIKE', "%{$search}%"))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function activePersonnelQuery(array $excludeIds, ?string $search): Builder
    {
        return Personnel::query()
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('surname', 'LIKE', "%{$search}%");
                });
            })
            ->whereIn('structure_id', $this->structureService->getAccessibleStructures())
            ->whereNotIn('id', $excludeIds)
            ->whereNull('leave_work_date')
            ->orderBy('position_id')
            ->orderBy('structure_id');
    }

    private function candidatePersonnelQuery(array $excludeIds, ?string $search): Builder
    {
        return Candidate::query()
            ->when($search, function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('surname', 'LIKE', "%{$search}%");
                });
            })
            ->whereNotIn('id', $excludeIds)
            ->where('status_id', 30);
    }
}
