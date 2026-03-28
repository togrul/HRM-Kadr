<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\Personnel;
use App\Models\SelfServiceApprovalRoute;
use App\Services\HrPolicies\HrPolicyPackService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ApprovalRouteResolverService
{
    public function __construct(
        private readonly HrPolicyPackService $policyPackService,
    ) {}

    /**
     * @var array<string, array<string, int|string|bool|null>>
     */
    private array $routeCache = [];

    /**
     * @var array<string, array{route: array<string, int|string|bool|null>, chain: array<int, array<string, int|string|null>>}>
     */
    private array $previewCache = [];

    /**
     * @var array<int, array<int, array<string, string|int|null>>>
     */
    private array $managerChainCache = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private array $policyCache = [];

    /**
     * @var array<string, SelfServiceApprovalRoute|null>|null
     */
    private ?array $routeOverridesCache = null;

    /**
     * @var array<string, array<int, Personnel>>
     */
    private array $approversCache = [];

    /**
     * @var array<int, array<int, int>>
     */
    private array $structureLineCache = [];

    /**
     * @var array<int, array{id:int,parent_id:int|null,name:string}|null>
     */
    private array $structureMetaCache = [];

    /**
     * @var array<int, string>
     */
    private array $structurePathCache = [];

    public function resolve(Personnel $personnel, string $requestType): array
    {
        return $this->preview($personnel, $requestType, 2)['route'];
    }

    /**
     * @return array{route: array<string, int|string|bool|null>, chain: array<int, array<string, int|string|null>>}
     */
    public function preview(Personnel $personnel, string $requestType, int $chainLimit = 5): array
    {
        $effectiveLimit = max(2, $chainLimit);
        $cacheKey = $personnel->getKey().':'.$requestType.':'.$effectiveLimit;

        if (array_key_exists($cacheKey, $this->previewCache)) {
            return $this->previewCache[$cacheKey];
        }

        $this->hydratePersonnelContext($personnel, withStructure: false);

        $policy = $this->policy($requestType);
        $approvers = $this->resolveHierarchyApprovers($personnel, $effectiveLimit);

        $primaryApprover = ($policy['include_primary_approver'] ?? true)
            ? ($approvers[0] ?? null)
            : null;

        $upperApprover = ($policy['include_upper_approver'] ?? false)
            ? ($approvers[1] ?? null)
            : null;

        $route = [
            'approver_personnel_id' => $primaryApprover?->id,
            'fallback_approver_personnel_id' => $upperApprover?->id,
            'approval_route_source' => ($primaryApprover || $upperApprover) ? 'hierarchy_policy' : 'hr_only_policy',
            'hr_always_included' => (bool) ($policy['hr_always_included'] ?? true),
        ];

        $this->routeCache[$personnel->getKey().':'.$requestType] = $route;

        return $this->previewCache[$cacheKey] = [
            'route' => $route,
            'chain' => collect($approvers)
                ->take($effectiveLimit)
                ->map(fn (Personnel $approver): array => $this->personnelCard($approver))
                ->values()
                ->all(),
        ];
    }

    public function managerChain(Personnel $personnel): array
    {
        $cacheKey = (int) $personnel->getKey();

        if (array_key_exists($cacheKey, $this->managerChainCache)) {
            return $this->managerChainCache[$cacheKey];
        }

        $chain = [];
        $visited = [];
        $cursor = $personnel;

        while ($manager = $this->resolveHierarchyApprover($cursor)) {
            if (isset($visited[$manager->id])) {
                break;
            }

            $visited[$manager->id] = true;
            $chain[] = $this->personnelCard($manager);
            $cursor = $manager;
        }

        return $this->managerChainCache[$cacheKey] = $chain;
    }

    public function directReports(Personnel $manager): array
    {
        $this->hydratePersonnelContext($manager, withStructure: false);

        $structureIds = $this->nestedStructureIds((int) $manager->structure_id);

        $candidates = Personnel::query()
            ->active()
            ->whereKeyNot($manager->id)
            ->whereIn('structure_id', array_filter($structureIds))
            ->with([
                'position:id,name,approval_rank,is_approval_target',
            ])
            ->orderBy('surname')
            ->orderBy('name')
            ->get([
                'id',
                'surname',
                'name',
                'patronymic',
                'position_id',
                'structure_id',
                'leave_work_date',
                'is_pending',
            ]);

        $byStructure = $candidates
            ->concat([$manager])
            ->groupBy('structure_id');

        return $candidates
            ->filter(function (Personnel $candidate) use ($manager, $byStructure): bool {
                $approver = $this->resolveHierarchyApproverFromPool($candidate, $byStructure, (int) $manager->structure_id);

                return $approver?->is($manager) ?? false;
            })
            ->map(fn (Personnel $candidate): array => $this->personnelCard($candidate))
            ->values()
            ->all();
    }

    public function manager(Personnel $personnel): ?array
    {
        $manager = $this->resolveHierarchyApprover($personnel);

        return $manager ? $this->personnelCard($manager) : null;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function personnelPreviewCard(Personnel $personnel): array
    {
        return $this->personnelCard($personnel);
    }

    private function resolveHierarchyApprover(Personnel $personnel): ?Personnel
    {
        return $this->resolveHierarchyApprovers($personnel, 1)[0] ?? null;
    }

    private function resolveHierarchyApproverFromPool(Personnel $personnel, Collection $byStructure, int $stopStructureId): ?Personnel
    {
        $this->hydratePersonnelContext($personnel, withStructure: false);

        $currentRank = (int) ($personnel->position?->approval_rank ?? 0);

        foreach ($this->structureLineUntilId((int) $personnel->structure_id, $stopStructureId) as $structureId) {
            /** @var Collection<int, Personnel> $candidates */
            $candidates = $byStructure->get($structureId, collect());

            $approver = $candidates
                ->filter(fn (Personnel $candidate): bool => $candidate->id !== $personnel->id)
                ->filter(fn (Personnel $candidate): bool => (bool) ($candidate->position?->is_approval_target ?? false))
                ->filter(fn (Personnel $candidate): bool => (int) ($candidate->position?->approval_rank ?? 0) > $currentRank)
                ->sortBy([
                    fn (Personnel $candidate): int => (int) ($candidate->position?->approval_rank ?? 0),
                    fn (Personnel $candidate): int => (int) $candidate->id,
                ])
                ->first();

            if ($approver) {
                return $approver;
            }
        }

        return null;
    }

    /**
     * @return array<int, int>
     */
    private function structureLineFromId(?int $structureId): array
    {
        if (! $structureId) {
            return [];
        }

        $cacheKey = (int) $structureId;

        if (array_key_exists($cacheKey, $this->structureLineCache)) {
            return $this->structureLineCache[$cacheKey];
        }

        $line = [];
        $cursorId = $structureId;

        while ($cursorId) {
            $meta = $this->loadStructureMeta($cursorId);

            if (! $meta) {
                break;
            }

            $line[] = (int) $meta['id'];
            $cursorId = $meta['parent_id'];
        }

        return $this->structureLineCache[$cacheKey] = $line;
    }

    /**
     * @return array<int, int>
     */
    private function structureLineUntilId(int $structureId, int $stopStructureId): array
    {
        $line = [];

        foreach ($this->structureLineFromId($structureId) as $currentStructureId) {
            $line[] = $currentStructureId;

            if ($currentStructureId === $stopStructureId) {
                break;
            }
        }

        return $line;
    }

    private function policy(string $requestType): array
    {
        if (array_key_exists($requestType, $this->policyCache)) {
            return $this->policyCache[$requestType];
        }

        $packPolicy = $this->policyPackService->selfServiceApproval($requestType);
        $route = $this->routeOverrides()[$requestType] ?? null;

        return $this->policyCache[$requestType] = [
            'include_primary_approver' => $route?->include_primary_approver ?? ($packPolicy['include_primary_approver'] ?? true),
            'include_upper_approver' => $route?->include_upper_approver ?? ($packPolicy['include_upper_approver'] ?? false),
            'hr_always_included' => $route?->hr_always_included ?? ($packPolicy['hr_always_included'] ?? true),
        ];
    }

    /**
     * @return array<string, SelfServiceApprovalRoute|null>
     */
    private function routeOverrides(): array
    {
        if ($this->routeOverridesCache !== null) {
            return $this->routeOverridesCache;
        }

        return $this->routeOverridesCache = Cache::remember(
            'self-service-approval-routes:active',
            now()->addMinutes(10),
            fn (): array => SelfServiceApprovalRoute::query()
                ->where('is_active', true)
                ->orderByDesc('id')
                ->get([
                    'id',
                    'request_type',
                    'include_primary_approver',
                    'include_upper_approver',
                    'hr_always_included',
                    'is_active',
                ])
                ->unique('request_type')
                ->keyBy('request_type')
                ->all()
        );
    }

    /**
     * @return array<int, Personnel>
     */
    private function resolveHierarchyApprovers(Personnel $personnel, int $limit): array
    {
        $cacheKey = $personnel->getKey().':'.$limit;

        if (array_key_exists($cacheKey, $this->approversCache)) {
            return $this->approversCache[$cacheKey];
        }

        $this->hydratePersonnelContext($personnel, withStructure: false);

        $currentRank = (int) ($personnel->position?->approval_rank ?? 0);
        $resolved = [];
        $seen = [];

        foreach ($this->structureLineFromId((int) $personnel->structure_id) as $structureId) {
            $candidates = Personnel::query()
                ->active()
                ->whereKeyNot($personnel->id)
                ->where('structure_id', $structureId)
                ->whereHas('position', fn (Builder $query) => $query
                    ->where('is_approval_target', true)
                    ->where('approval_rank', '>', $currentRank))
                ->with([
                    'position:id,name,approval_rank,is_approval_target',
                ])
                ->join('positions', 'positions.id', '=', 'personnels.position_id')
                ->orderBy('positions.approval_rank')
                ->orderBy('personnels.id')
                ->select('personnels.*')
                ->get();

            foreach ($candidates as $candidate) {
                if (isset($seen[$candidate->id])) {
                    continue;
                }

                $seen[$candidate->id] = true;
                $resolved[] = $candidate;

                if (count($resolved) >= $limit) {
                    return $this->approversCache[$cacheKey] = $resolved;
                }
            }
        }

        return $this->approversCache[$cacheKey] = $resolved;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function personnelCard(Personnel $personnel): array
    {
        $this->hydratePersonnelContext($personnel, withStructure: false);

        return [
            'id' => (int) $personnel->id,
            'fullname' => $personnel->fullname,
            'position' => $personnel->position?->name ?: '—',
            'structure' => $this->structurePathLabel($personnel),
        ];
    }

    private function hydratePersonnelContext(Personnel $personnel, bool $withStructure = true): void
    {
        $personnel->loadMissing([
            'position:id,name,approval_rank,is_approval_target',
        ]);

        if (! $withStructure || ! $personnel->structure_id) {
            return;
        }
    }

    private function loadStructureMeta(int $structureId): ?array
    {
        if (array_key_exists($structureId, $this->structureMetaCache)) {
            return $this->structureMetaCache[$structureId];
        }

        $row = \App\Models\Structure::query()
            ->select('id', 'parent_id', 'name')
            ->find($structureId);

        return $this->structureMetaCache[$structureId] = $row
            ? [
                'id' => (int) $row->id,
                'parent_id' => $row->parent_id ? (int) $row->parent_id : null,
                'name' => (string) $row->name,
            ]
            : null;
    }

    private function structurePathLabel(Personnel $personnel): string
    {
        $structureId = (int) ($personnel->structure_id ?? 0);

        if (! $structureId) {
            return '—';
        }

        if (array_key_exists($structureId, $this->structurePathCache)) {
            return $this->structurePathCache[$structureId];
        }

        $segments = [];
        $cursorId = $structureId;

        while ($cursorId) {
            $meta = $this->loadStructureMeta($cursorId);

            if (! $meta) {
                break;
            }

            $segments[] = $meta['name'];
            $cursorId = $meta['parent_id'];
        }

        return $this->structurePathCache[$structureId] = $segments === []
            ? '—'
            : implode(' / ', array_reverse($segments));
    }

    /**
     * @return array<int, int>
     */
    private function nestedStructureIds(int $rootStructureId): array
    {
        $pending = [$rootStructureId];
        $resolved = [];

        while ($pending !== []) {
            $batch = array_values(array_diff($pending, $resolved));
            $pending = [];

            if ($batch === []) {
                continue;
            }

            $resolved = array_merge($resolved, $batch);

            $children = \App\Models\Structure::query()
                ->whereIn('parent_id', $batch)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($children as $childId) {
                if (! in_array($childId, $resolved, true)) {
                    $pending[] = $childId;
                }
            }
        }

        return $resolved;
    }
}
