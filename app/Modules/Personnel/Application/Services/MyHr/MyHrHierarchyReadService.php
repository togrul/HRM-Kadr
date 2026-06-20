<?php

namespace App\Modules\Personnel\Application\Services\MyHr;

use App\Models\Personnel;

class MyHrHierarchyReadService
{
    public function __construct(
        private readonly ApprovalRouteResolverService $approvalRouteResolver,
    ) {}

    public function build(Personnel $personnel): array
    {
        $personnel->loadMissing([
            'position:id,name',
            'structure' => fn ($query) => $query->select('id', 'parent_id', 'name')->withRecursive('parent', false),
        ]);

        $manager = $this->approvalRouteResolver->manager($personnel);
        $managerChain = $this->approvalRouteResolver->managerChain($personnel);
        $directReports = $this->approvalRouteResolver->directReports($personnel);
        $approvalRoutes = $this->approvalRoutes($personnel);

        return [
            'summary' => [
                'manager' => $manager ?? $this->personnelCard(null),
                'structure' => $personnel->structure?->fullStructureName(includeRoot: true) ?: '—',
                'chain_count' => count($managerChain),
                'direct_reports_count' => count($directReports),
            ],
            'self' => $this->personnelCard($personnel),
            'manager_chain' => $managerChain,
            'direct_reports' => $directReports,
            'approval_routes' => $approvalRoutes,
        ];
    }

    private function approvalRoutes(Personnel $personnel): array
    {
        $routes = [
            'leave' => $this->approvalRouteResolver->resolve($personnel, 'leave'),
            'vacation' => $this->approvalRouteResolver->resolve($personnel, 'vacation'),
            'business_trip' => $this->approvalRouteResolver->resolve($personnel, 'business_trip'),
        ];

        $personnelIds = collect($routes)
            ->flatMap(fn (array $route): array => array_filter([
                $route['approver_personnel_id'] ?? null,
                $route['fallback_approver_personnel_id'] ?? null,
            ]))
            ->unique()
            ->values();

        $people = Personnel::query()
            ->with([
                'position:id,name',
                'structure' => fn ($query) => $query->select('id', 'parent_id', 'name')->withRecursive('parent', false),
            ])
            ->whereKey($personnelIds)
            ->get()
            ->keyBy('id');

        return collect($routes)
            ->map(function (array $route, string $type) use ($people): array {
                return [
                    'type' => $type,
                    'approver' => $this->personnelCard($people->get($route['approver_personnel_id'] ?? 0)),
                    'fallback_approver' => $this->personnelCard($people->get($route['fallback_approver_personnel_id'] ?? 0)),
                    'source' => (string) ($route['approval_route_source'] ?? 'hr_only_policy'),
                    'hr_always_included' => (bool) ($route['hr_always_included'] ?? true),
                    'primary_enabled' => (bool) (($route['approver_personnel_id'] ?? null) !== null),
                    'upper_enabled' => (bool) (($route['fallback_approver_personnel_id'] ?? null) !== null),
                ];
            })
            ->values()
            ->all();
    }

    private function personnelCard(?Personnel $personnel): array
    {
        if (! $personnel) {
            return [
                'id' => null,
                'fullname' => '—',
                'position' => '—',
                'structure' => '—',
            ];
        }

        return [
            'id' => $personnel->id,
            'fullname' => $personnel->fullname,
            'position' => $personnel->position?->name ?: '—',
            'structure' => $personnel->structure?->fullStructureName(includeRoot: true) ?: '—',
        ];
    }
}
