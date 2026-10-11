<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\Structure;
use App\Models\User;
use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseType;
use App\Services\StructureService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Anonimləşdirilmiş statistika: struktur və növ üzrə iş sayı, təkrarlanan hallar
 * (eyni işçi barəsində N və daha çox iş), orta araşdırma müddəti, SLA pozuntuları.
 *
 * Yalnız istifadəçinin struktur görünürlüyündəki işlər sayılır (URL-dəki struktur filtri
 * görünürlüklə kəsişdirilir). Sıfırdan böyük, `min_group_size`-dan kiçik hər say
 * gizlədilir (null) — kiçik qrupda şəxsin tanınmaması üçün. Ad, iş nömrəsi və ya
 * şəxs səviyyəli sətir qaytarılmır.
 */
class StatisticsService
{
    public function __construct(private readonly StructureService $structures) {}

    public function minGroupSize(): int
    {
        return max(1, (int) config('employee_relations.statistics.min_group_size', 3));
    }

    public function repeatThreshold(): int
    {
        return max(2, (int) config('employee_relations.statistics.repeat_threshold', 2));
    }

    /**
     * @return array{
     *     total: ?int,
     *     open: ?int,
     *     overdue: ?int,
     *     sla_breaches: ?int,
     *     repeat_subjects: ?int,
     *     avg_investigation_days: ?float,
     *     by_structure: list<array{label: string, value: ?int}>,
     *     by_type: list<array{label: string, value: ?int}>,
     *     by_status: list<array{label: string, value: ?int}>,
     *     min_group_size: int,
     *     repeat_threshold: int
     * }
     *
     * @throws AuthorizationException
     */
    public function build(User $viewer, int|string|null $structureId = null, ?string $from = null, ?string $to = null): array
    {
        if (! $viewer->can(CaseAccess::STATISTICS)) {
            throw new AuthorizationException;
        }

        $scope = $this->structures->scopeFor($viewer)->intersect($structureId);

        $base = function () use ($scope, $from, $to): Builder {
            $query = RelationCase::query();
            $scope->constrain($query, 'employee_relation_cases.structure_id');

            if (filled($from)) {
                $query->where('employee_relation_cases.created_at', '>=', Carbon::parse((string) $from)->startOfDay());
            }

            if (filled($to)) {
                $query->where('employee_relation_cases.created_at', '<=', Carbon::parse((string) $to)->endOfDay());
            }

            return $query;
        };

        $slaStatuses = array_values(array_map(
            fn (CaseStatus $status): string => $status->value,
            array_filter(CaseStatus::cases(), fn (CaseStatus $status): bool => $status->hasSla()),
        ));

        $overdue = $base()
            ->whereIn('status', $slaStatuses)
            ->whereNotNull('stage_due_at')
            ->where('stage_due_at', '<', now())
            ->count();

        $repeatSubjects = DB::query()
            ->fromSub(
                $base()
                    ->whereNotNull('subject_personnel_id')
                    ->select('subject_personnel_id')
                    ->groupBy('subject_personnel_id')
                    ->havingRaw('count(*) >= ?', [$this->repeatThreshold()])
                    ->toBase(),
                'repeat_subjects',
            )
            ->count();

        $durations = $base()
            ->whereNotNull('decided_at')
            ->get(['created_at', 'decided_at'])
            ->map(fn (RelationCase $case): float => max(0.0, (float) $case->created_at?->diffInHours($case->decided_at) / 24));

        $structureCounts = $base()
            ->selectRaw('structure_id, count(*) as aggregate')
            ->groupBy('structure_id')
            ->pluck('aggregate', 'structure_id');
        $structureNames = Structure::query()->whereIn('id', $structureCounts->keys()->filter()->all())->pluck('name', 'id');

        $typeCounts = $base()
            ->selectRaw('case_type_id, count(*) as aggregate')
            ->groupBy('case_type_id')
            ->pluck('aggregate', 'case_type_id');
        $typeNames = RelationCaseType::query()->whereIn('id', $typeCounts->keys()->all())->pluck('name', 'id');

        $statusCounts = $base()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total' => $this->suppress($base()->count()),
            'open' => $this->suppress($base()->where('status', '!=', CaseStatus::Closed->value)->count()),
            'overdue' => $this->suppress($overdue),
            'sla_breaches' => $this->suppress((int) $base()->sum('sla_breach_count') + $overdue),
            'repeat_subjects' => $this->suppress($repeatSubjects),
            'avg_investigation_days' => $durations->count() >= $this->minGroupSize() ? round((float) $durations->avg(), 1) : null,
            'by_structure' => $this->rows($structureCounts->all(), fn ($id): string => $id ? (string) ($structureNames[$id] ?? '—') : __('employee_relations::cases.labels.no_structure')),
            'by_type' => $this->rows($typeCounts->all(), fn ($id): string => (string) ($typeNames[$id] ?? '—')),
            'by_status' => $this->rows($statusCounts->all(), fn ($status): string => CaseStatus::tryFrom((string) $status)?->label() ?? (string) $status),
            'min_group_size' => $this->minGroupSize(),
            'repeat_threshold' => $this->repeatThreshold(),
        ];
    }

    public function suppress(int $value): ?int
    {
        return $value > 0 && $value < $this->minGroupSize() ? null : $value;
    }

    /**
     * @param  array<int|string, int|string>  $counts
     * @param  callable(int|string): string  $label
     * @return list<array{label: string, value: ?int}>
     */
    private function rows(array $counts, callable $label): array
    {
        $rows = [];

        foreach ($counts as $key => $count) {
            $rows[] = ['label' => $label($key), 'value' => $this->suppress((int) $count)];
        }

        usort($rows, fn (array $a, array $b): int => ($b['value'] ?? 0) <=> ($a['value'] ?? 0) ?: strcmp($a['label'], $b['label']));

        return $rows;
    }
}
