<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\Personnel;
use App\Models\SelfServiceApprovalRoute;
use App\Models\Structure;
use App\Services\HrPolicies\HrPolicyPackService;
use Illuminate\Database\Eloquent\Builder;

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

        return $candidates
            ->filter(fn (Personnel $candidate): bool => $this->resolveHierarchyApprover($candidate)?->is($manager) ?? false)
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

    /**
     * @return array<int, int>
     */
    private function structureLine(?Structure $structure): array
    {
        if (! $structure) {
            return [];
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

        return $line;
    }

    private function policy(string $requestType): array
    {
        $packPolicy = $this->policyPackService->selfServiceApproval($requestType);
        $route = SelfServiceApprovalRoute::query()
            ->where('request_type', $requestType)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        return [
            'include_primary_approver' => $route?->include_primary_approver ?? ($packPolicy['include_primary_approver'] ?? true),
            'include_upper_approver' => $route?->include_upper_approver ?? ($packPolicy['include_upper_approver'] ?? false),
            'hr_always_included' => $route?->hr_always_included ?? ($packPolicy['hr_always_included'] ?? true),
        ];
    }

    /**
     * @return array<int, Personnel>
     */
    private function resolveHierarchyApprovers(Personnel $personnel, int $limit): array
    {
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
                    return $resolved;
                }
            }
        }

        return $resolved;
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
