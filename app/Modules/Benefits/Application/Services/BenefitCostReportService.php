<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\BenefitEnrollment;
use App\Services\StructureScope;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * İmtiyaz xərcləri hesabatı: seçilmiş ayda qüvvədə olan qoşulmalar üzrə işəgötürən xərci, işçi
 * payı, qoşulan və ailə üzvü sayı — struktur və paket üzrə. Sətirlər istifadəçinin struktur
 * görünürlüyü ilə məhdudlaşır (fail closed); struktur süzgəci görünürlüklə kəsişdirilir.
 */
class BenefitCostReportService
{
    /**
     * @param  array{month?: ?string, plan_id?: ?int, structure_id?: ?int}  $filters
     * @return array{
     *     month: string,
     *     by_plan: list<array{plan_id: int, plan: string, type: string, enrollees: int, dependents: int, employer: float, employee: float, total: float}>,
     *     by_structure: list<array{structure_id: ?int, structure: string, enrollees: int, dependents: int, employer: float, employee: float, total: float}>,
     *     totals: array{enrollees: int, dependents: int, employer: float, employee: float, total: float}
     * }
     */
    public function report(StructureScope $scope, array $filters = []): array
    {
        $month = $this->month($filters['month'] ?? null);
        $start = $month->startOfMonth()->toDateString();
        $end = $month->endOfMonth()->toDateString();

        $structureFilter = (int) ($filters['structure_id'] ?? 0);
        $effectiveScope = $structureFilter > 0 ? $scope->intersect($this->subtree($structureFilter)) : $scope;

        $query = DB::table('benefit_enrollments')
            ->join('personnels', 'personnels.tabel_no', '=', 'benefit_enrollments.tabel_no')
            ->join('benefit_plans', 'benefit_plans.id', '=', 'benefit_enrollments.benefit_plan_id')
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->where('benefit_enrollments.status', '!=', BenefitEnrollment::STATUS_CANCELLED)
            ->whereDate('benefit_enrollments.start_date', '<=', $end)
            ->where(fn ($q) => $q->whereNull('benefit_enrollments.end_date')->orWhereDate('benefit_enrollments.end_date', '>=', $start))
            ->when((int) ($filters['plan_id'] ?? 0) > 0, fn ($q) => $q->where('benefit_enrollments.benefit_plan_id', (int) $filters['plan_id']));

        $effectiveScope->constrain($query, 'personnels.structure_id');

        $rows = $query->get([
            'benefit_enrollments.id',
            'benefit_enrollments.employer_amount',
            'benefit_enrollments.employee_amount',
            'benefit_plans.id as plan_id',
            'benefit_plans.name as plan_name',
            'benefit_plans.type as plan_type',
            'personnels.structure_id',
            'structures.name as structure_name',
        ]);

        $dependentCounts = $rows->isEmpty()
            ? collect()
            : DB::table('benefit_enrollment_dependents')
                ->whereIn('benefit_enrollment_id', $rows->pluck('id')->all())
                ->groupBy('benefit_enrollment_id')
                ->selectRaw('benefit_enrollment_id, count(*) as aggregate')
                ->pluck('aggregate', 'benefit_enrollment_id');

        $summarize = fn (Collection $group): array => [
            'enrollees' => $group->count(),
            'dependents' => (int) $group->sum(fn (object $row): int => (int) ($dependentCounts[$row->id] ?? 0)),
            'employer' => round((float) $group->sum(fn (object $row): float => (float) $row->employer_amount), 2),
            'employee' => round((float) $group->sum(fn (object $row): float => (float) $row->employee_amount), 2),
            'total' => round((float) $group->sum(fn (object $row): float => (float) $row->employer_amount + (float) $row->employee_amount), 2),
        ];

        $byPlan = $rows->groupBy('plan_id')
            ->map(fn (Collection $group, $planId): array => [
                'plan_id' => (int) $planId,
                'plan' => (string) $group->first()->plan_name,
                'type' => (string) $group->first()->plan_type,
                ...$summarize($group),
            ])
            ->sortByDesc('employer')
            ->values()
            ->all();

        $byStructure = $rows->groupBy(fn (object $row): string => (string) ($row->structure_id ?? 0))
            ->map(fn (Collection $group, $structureId): array => [
                'structure_id' => (int) $structureId > 0 ? (int) $structureId : null,
                'structure' => (string) ($group->first()->structure_name ?? '—'),
                ...$summarize($group),
            ])
            ->sortByDesc('employer')
            ->values()
            ->all();

        return [
            'month' => $month->format('Y-m'),
            'by_plan' => $byPlan,
            'by_structure' => $byStructure,
            'totals' => $summarize($rows),
        ];
    }

    private function month(?string $value): CarbonImmutable
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return CarbonImmutable::createFromFormat('Y-m-d', $value.'-01')->startOfDay();
        }

        return CarbonImmutable::today()->startOfMonth();
    }

    /**
     * Seçilmiş struktur və onun bütün alt strukturları.
     *
     * @return list<int>
     */
    private function subtree(int $structureId): array
    {
        $children = DB::table('structures')->get(['id', 'parent_id'])
            ->groupBy(fn (object $row): int => (int) $row->parent_id);

        $ids = [];
        $stack = [$structureId];

        while ($stack !== []) {
            $id = array_pop($stack);

            if (isset($ids[$id])) {
                continue;
            }

            $ids[$id] = $id;

            foreach ($children->get($id, collect()) as $child) {
                $stack[] = (int) $child->id;
            }
        }

        return array_values($ids);
    }
}
