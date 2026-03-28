<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\Personnel;
use App\Models\SelfServiceApprovalRoute;
use App\Models\Structure;
use App\Services\HrPolicies\HrPolicyPackService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

    public function resolve(Personnel $personnel, string $requestType): array
    {
        $cacheKey = $personnel->getKey().':'.$requestType;

        if (array_key_exists($cacheKey, $this->routeCache)) {
            return $this->routeCache[$cacheKey];
        }

        $personnel->loadMissing([
            'position:id,name,approval_rank,is_approval_target',
            'structure' => fn ($query) => $query->select('id', 'parent_id', 'name')->withRecursive('parent', false),
        ]);

        $policy = $this->policy($requestType);
        $approvers = $this->resolveHierarchyApprovers($personnel, 2);

        $primaryApprover = ($policy['include_primary_approver'] ?? true)
            ? ($approvers[0] ?? null)
            : null;

        $upperApprover = ($policy['include_upper_approver'] ?? false)
            ? ($approvers[1] ?? null)
            : null;

        if ($primaryApprover || $upperApprover) {
            return $this->routeCache[$cacheKey] = [
                'approver_personnel_id' => $primaryApprover?->id,
                'fallback_approver_personnel_id' => $upperApprover?->id,
                'approval_route_source' => 'hierarchy_policy',
                'hr_always_included' => (bool) ($policy['hr_always_included'] ?? true),
            ];
        }

        return $this->routeCache[$cacheKey] = [
            'approver_personnel_id' => null,
            'fallback_approver_personnel_id' => null,
            'approval_route_source' => 'hr_only_policy',
            'hr_always_included' => (bool) ($policy['hr_always_included'] ?? true),
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
        $manager->loadMissing([
            'position:id,name,approval_rank,is_approval_target',
            'structure' => fn ($query) => $query->select('id', 'parent_id', 'name')->withRecursive('parent', false),
        ]);

        $structureIds = $manager->structure
            ? $manager->structure->getAllNestedIds()
            : [(int) $manager->structure_id];

        $candidates = Personnel::query()
            ->active()
            ->whereKeyNot($manager->id)
            ->whereIn('structure_id', array_filter($structureIds))
            ->with([
                'position:id,name,approval_rank,is_approval_target',
                'structure' => fn ($query) => $query->select('id', 'parent_id', 'name')->withRecursive('parent', false),
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

    private function resolveHierarchyApprover(Personnel $personnel): ?Personnel
    {
        return $this->resolveHierarchyApprovers($personnel, 1)[0] ?? null;
    }

    private function resolveHierarchyApproverFromPool(Personnel $personnel, Collection $byStructure, int $stopStructureId): ?Personnel
    {
        $personnel->loadMissing([
            'position:id,name,approval_rank,is_approval_target',
            'structure' => fn ($query) => $query->select('id', 'parent_id', 'name')->withRecursive('parent', false),
        ]);

        $currentRank = (int) ($personnel->position?->approval_rank ?? 0);

        foreach ($this->structureLineUntil($personnel->structure, $stopStructureId) as $structureId) {
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
    private function structureLine(?Structure $structure): array
    {
        if (! $structure) {
            return [];
        }

        $cacheKey = (int) $structure->id;

        if (array_key_exists($cacheKey, $this->structureLineCache)) {
            return $this->structureLineCache[$cacheKey];
        }

        $line = [];
        $cursor = $structure;

        while ($cursor) {
            $line[] = (int) $cursor->id;

            if (! $cursor->relationLoaded('parent') && ! is_null($cursor->parent_id)) {
                $cursor->loadMissing('parent');
            }

            $cursor = $cursor->parent;
        }

        return $this->structureLineCache[$cacheKey] = $line;
    }

    /**
     * @return array<int, int>
     */
    private function structureLineUntil(?Structure $structure, int $stopStructureId): array
    {
        $line = [];

        foreach ($this->structureLine($structure) as $structureId) {
            $line[] = $structureId;

            if ($structureId === $stopStructureId) {
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

        return $this->routeOverridesCache = SelfServiceApprovalRoute::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get()
            ->unique('request_type')
            ->keyBy('request_type')
            ->all();
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

        $personnel->loadMissing([
            'position:id,name,approval_rank,is_approval_target',
            'structure' => fn ($query) => $query->select('id', 'parent_id', 'name')->withRecursive('parent', false),
        ]);

        $currentRank = (int) ($personnel->position?->approval_rank ?? 0);
        $resolved = [];
        $seen = [];

        foreach ($this->structureLine($personnel->structure) as $structureId) {
            $candidates = Personnel::query()
                ->active()
                ->whereKeyNot($personnel->id)
                ->where('structure_id', $structureId)
                ->whereHas('position', fn (Builder $query) => $query
                    ->where('is_approval_target', true)
                    ->where('approval_rank', '>', $currentRank))
                ->with([
                    'position:id,name,approval_rank,is_approval_target',
                    'structure' => fn ($query) => $query->select('id', 'parent_id', 'name')->withRecursive('parent', false),
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
        return [
            'id' => (int) $personnel->id,
            'fullname' => $personnel->fullname,
            'position' => $personnel->position?->name ?: '—',
            'structure' => $personnel->structure?->fullStructureName(includeRoot: true) ?: '—',
        ];
    }
}
