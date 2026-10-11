<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\User;
use App\Services\StructureScope;
use App\Services\StructureService;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * HR Command Center: rəhbər/HR üçün ana səhifədəki «diqqət tələb edənlər» panelləri —
 * gecikmiş həyat dövrü tapşırıqları, sınaq müddəti baxışları, açıq vakansiyalar,
 * tələbnamə yaşı, açıq davamiyyət istisnaları və bitmək üzrə olan sənədlər.
 *
 * Hər panel:
 *  - öz icazəsi ilə qapılıdır (görünməyən panel heç bir sorğu işlətmir);
 *  - baxanın struktur görünürlüyü (StructureScope) ilə süzülür — struktursuz sətir məhdud
 *    baxana görünmür (fail closed);
 *  - iki sorğudur: tam say (aqreqat) + məhdud sətirlər (LIMIT), N+1 yoxdur;
 *  - indeksli sütunlarla süzülür (status + tarix).
 *
 * HomeOverviewService kimi modullararası məlumat yalnız ortaq cədvəllərdən oxunur.
 */
class HrCommandCenterReadService
{
    private const CLOSED = ['completed', 'cancelled'];

    /** Bitmə tarixi olan işçi sənədləri: cədvəl => [tarix sütunu, sənəd növü açarı]. */
    private const DOCUMENT_SOURCES = [
        'personnel_cards' => ['valid_date', 'service_card'],
        'personnel_passports' => ['valid_date', 'passport'],
        'personnel_identity_documents' => ['valid_date', 'id_card'],
        'personnel_contracts' => ['contract_ends_at', 'contract'],
    ];

    /**
     * Panellərin tərtibi: açar => [icazələr (hər hansı biri), marşrut, marşrut parametrləri, oxuyan metod].
     *
     * @var array<string, array{0: list<string>, 1: string, 2: array<string, mixed>, 3: string}>
     */
    private const PANELS = [
        'lifecycle_overdue' => [['show-employee-lifecycle'], 'employee-lifecycle', [], 'overdueLifecycleTasks'],
        'probation_reviews' => [['show-employee-lifecycle'], 'employee-lifecycle', [], 'probationReviews'],
        'open_vacancies' => [['show-candidates'], 'candidates.openings', [], 'openVacancies'],
        'requisition_aging' => [['show-candidates'], 'candidates.requisitions', [], 'requisitionAging'],
        'attendance_exceptions' => [['show-attendance-exceptions', 'edit-attendance-exceptions'], 'attendance', ['tab' => 'exceptions'], 'attendanceExceptions'],
        'expiring_documents' => [['show-document-compliance'], 'document-compliance', [], 'expiringDocuments'],
    ];

    /** Baxan heç olmasa bir paneli görə bilərmi (ada island-ı yalnız onda qeydiyyatdan keçir). */
    public function visibleTo(?User $viewer): bool
    {
        return $this->visiblePanels($viewer) !== [];
    }

    /**
     * @return list<array{key: string, route: string, params: array<string, mixed>, count: int, stale: int|null, rows: list<array{id: int, title: string, meta: string, badge: string|null, tone: string}>}>
     */
    public function panels(?User $viewer): array
    {
        if (! $viewer instanceof User) {
            return [];
        }

        $scope = app(StructureService::class)->scopeFor($viewer);
        $limit = max(1, (int) config('strategic_hr.command_center.rows', 5));
        $panels = [];

        foreach ($this->visiblePanels($viewer) as $key => [$permissions, $route, $params, $reader]) {
            $result = $this->{$reader}($scope, $limit);

            if ($result === null) {
                continue;
            }

            $panels[] = [
                'key' => $key,
                'route' => $route,
                'params' => $params,
                'count' => $result['count'],
                'stale' => $result['stale'] ?? null,
                'rows' => $result['rows'],
            ];
        }

        return $panels;
    }

    /**
     * @return array<string, array{0: list<string>, 1: string, 2: array<string, mixed>, 3: string}>
     */
    private function visiblePanels(?User $viewer): array
    {
        if (! $viewer instanceof User) {
            return [];
        }

        return array_filter(self::PANELS, function (array $panel) use ($viewer): bool {
            if (! Route::has($panel[1])) {
                return false;
            }

            foreach ($panel[0] as $permission) {
                if ($viewer->can($permission)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * @return array{count: int, rows: list<array{id: int, title: string, meta: string, badge: string|null, tone: string}>}|null
     */
    private function overdueLifecycleTasks(StructureScope $scope, int $limit): ?array
    {
        if (! InstalledTables::has('employee_lifecycle_tasks') || ! InstalledTables::has('employee_lifecycle_events')) {
            return null;
        }

        $today = CarbonImmutable::today();
        $query = DB::table('employee_lifecycle_tasks')
            ->join('employee_lifecycle_events', 'employee_lifecycle_events.id', '=', 'employee_lifecycle_tasks.event_id')
            ->join('personnels', 'personnels.id', '=', 'employee_lifecycle_events.personnel_id')
            ->whereNotIn('employee_lifecycle_tasks.status', self::CLOSED)
            ->whereNotIn('employee_lifecycle_events.status', self::CLOSED)
            ->where('employee_lifecycle_tasks.due_at', '<', $today->toDateString());
        $this->constrain($query, $scope);

        $count = (clone $query)->count();
        $rows = $count === 0 ? collect() : $query
            ->orderBy('employee_lifecycle_tasks.due_at')
            ->orderBy('employee_lifecycle_tasks.id')
            ->limit($limit)
            ->get([
                'employee_lifecycle_tasks.id',
                'employee_lifecycle_tasks.title',
                'employee_lifecycle_tasks.due_at',
                'employee_lifecycle_events.title as event_title',
                ...$this->nameColumns(),
            ]);

        return [
            'count' => $count,
            'rows' => $rows->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'meta' => $this->fullName($row).' · '.$row->event_title,
                'badge' => __('personnel::home.command.overdue_days', ['days' => $this->daysBetween($row->due_at, $today)]),
                'tone' => 'rose',
            ])->values()->all(),
        ];
    }

    /**
     * @return array{count: int, rows: list<array{id: int, title: string, meta: string, badge: string|null, tone: string}>}|null
     */
    private function probationReviews(StructureScope $scope, int $limit): ?array
    {
        if (! InstalledTables::has('employee_lifecycle_probation_reviews')) {
            return null;
        }

        $today = CarbonImmutable::today();
        $until = $today->addDays((int) config('strategic_hr.command_center.probation_window_days', 14));
        $query = DB::table('employee_lifecycle_probation_reviews')
            ->join('personnels', 'personnels.id', '=', 'employee_lifecycle_probation_reviews.personnel_id')
            ->where('employee_lifecycle_probation_reviews.status', 'pending')
            ->where('employee_lifecycle_probation_reviews.review_due_at', '<=', $until->toDateString());
        $this->constrain($query, $scope);

        $count = (clone $query)->count();
        $rows = $count === 0 ? collect() : $query
            ->orderBy('employee_lifecycle_probation_reviews.review_due_at')
            ->orderBy('employee_lifecycle_probation_reviews.id')
            ->limit($limit)
            ->get(['employee_lifecycle_probation_reviews.id', 'employee_lifecycle_probation_reviews.review_due_at', ...$this->nameColumns()]);

        return [
            'count' => $count,
            'rows' => $rows->map(function (object $row) use ($today): array {
                $due = CarbonImmutable::parse($row->review_due_at)->startOfDay();
                $overdue = $due->lt($today);

                return [
                    'id' => (int) $row->id,
                    'title' => $this->fullName($row),
                    'meta' => __('personnel::home.command.due_on', ['date' => $due->format('d.m.Y')]),
                    'badge' => $overdue
                        ? __('personnel::home.command.overdue_days', ['days' => $this->daysBetween($due, $today)])
                        : __('personnel::home.command.in_days', ['days' => $this->daysBetween($today, $due)]),
                    'tone' => $overdue ? 'rose' : 'amber',
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array{count: int, rows: list<array{id: int, title: string, meta: string, badge: string|null, tone: string}>}|null
     */
    private function openVacancies(StructureScope $scope, int $limit): ?array
    {
        if (! InstalledTables::has('job_openings')) {
            return null;
        }

        $today = CarbonImmutable::today();
        $query = DB::table('job_openings')->where('job_openings.status', 'open');
        $scope->constrain($query, 'job_openings.structure_id');

        $count = (clone $query)->count();
        $rows = $count === 0 ? collect() : $query
            ->leftJoin('structures', 'structures.id', '=', 'job_openings.structure_id')
            ->orderBy('job_openings.created_at')
            ->orderBy('job_openings.id')
            ->limit($limit)
            ->get(['job_openings.id', 'job_openings.title', 'job_openings.headcount', 'job_openings.published_at', 'job_openings.created_at', 'structures.name as structure_name']);

        return [
            'count' => $count,
            'rows' => $rows->map(function (object $row) use ($today): array {
                $days = $this->daysBetween($row->published_at ?? $row->created_at, $today);

                return [
                    'id' => (int) $row->id,
                    'title' => (string) $row->title,
                    'meta' => trim(($row->structure_name ? $row->structure_name.' · ' : '').__('personnel::home.command.headcount', ['count' => (int) $row->headcount])),
                    'badge' => __('personnel::home.command.open_for_days', ['days' => $days]),
                    'tone' => $days >= 30 ? 'amber' : 'neutral',
                ];
            })->values()->all(),
        ];
    }

    /**
     * Tələbnamə yaşı (requisition aging): təsdiq gözləyən/qaralama tələbnamələr, ən köhnəsi əvvəl.
     *
     * @return array{count: int, stale: int, rows: list<array{id: int, title: string, meta: string, badge: string|null, tone: string}>}|null
     */
    private function requisitionAging(StructureScope $scope, int $limit): ?array
    {
        if (! InstalledTables::has('job_requisitions') || ! InstalledTables::hasColumn('job_requisitions', 'approval_status')) {
            return null;
        }

        $today = CarbonImmutable::today();
        $staleDays = (int) config('strategic_hr.command_center.requisition_stale_days', 14);
        $staleBefore = $today->subDays($staleDays)->endOfDay()->toDateTimeString();
        $query = DB::table('job_requisitions')->whereIn('job_requisitions.approval_status', ['draft', 'pending']);
        $scope->constrain($query, 'job_requisitions.structure_id');

        $totals = (clone $query)
            ->selectRaw('COUNT(*) as aggregate, SUM(CASE WHEN job_requisitions.created_at <= ? THEN 1 ELSE 0 END) as stale', [$staleBefore])
            ->first();
        $count = (int) ($totals->aggregate ?? 0);

        $rows = $count === 0 ? collect() : $query
            ->orderBy('job_requisitions.created_at')
            ->orderBy('job_requisitions.id')
            ->limit($limit)
            ->get(['job_requisitions.id', 'job_requisitions.title', 'job_requisitions.approval_status', 'job_requisitions.headcount', 'job_requisitions.created_at']);

        return [
            'count' => $count,
            'stale' => (int) ($totals->stale ?? 0),
            'rows' => $rows->map(function (object $row) use ($today, $staleDays): array {
                $days = $this->daysBetween($row->created_at, $today);

                return [
                    'id' => (int) $row->id,
                    'title' => (string) $row->title,
                    'meta' => __('personnel::home.command.requisition_statuses.'.($row->approval_status === 'pending' ? 'pending' : 'draft')).' · '.__('personnel::home.command.headcount', ['count' => (int) $row->headcount]),
                    'badge' => __('personnel::home.command.age_days', ['days' => $days]),
                    'tone' => $days >= $staleDays ? 'rose' : 'neutral',
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array{count: int, rows: list<array{id: int, title: string, meta: string, badge: string|null, tone: string}>}|null
     */
    private function attendanceExceptions(StructureScope $scope, int $limit): ?array
    {
        if (! InstalledTables::has('attendance_exceptions')) {
            return null;
        }

        $today = CarbonImmutable::today();
        $query = DB::table('attendance_exceptions')
            ->join('personnels', 'personnels.tabel_no', '=', 'attendance_exceptions.tabel_no')
            ->where('attendance_exceptions.status', 'open');
        $this->constrain($query, $scope);

        $count = (clone $query)->count();
        $rows = $count === 0 ? collect() : $query
            ->orderBy('attendance_exceptions.date')
            ->orderBy('attendance_exceptions.id')
            ->limit($limit)
            ->get(['attendance_exceptions.id', 'attendance_exceptions.type', 'attendance_exceptions.date', ...$this->nameColumns()]);

        return [
            'count' => $count,
            'rows' => $rows->map(function (object $row) use ($today): array {
                $typeKey = 'personnel::home.command.exception_types.'.$row->type;

                return [
                    'id' => (int) $row->id,
                    'title' => $this->fullName($row),
                    'meta' => (trans()->has($typeKey) ? __($typeKey) : __('personnel::home.command.exception_types.other')).' · '.CarbonImmutable::parse($row->date)->format('d.m.Y'),
                    'badge' => __('personnel::home.command.age_days', ['days' => $this->daysBetween($row->date, $today)]),
                    'tone' => 'amber',
                ];
            })->values()->all(),
        ];
    }

    /**
     * Bütün sənəd cədvəlləri bir UNION ALL ilə: bir sorğu say, bir sorğu ilk sətirlər.
     *
     * @return array{count: int, rows: list<array{id: int, title: string, meta: string, badge: string|null, tone: string}>}|null
     */
    private function expiringDocuments(StructureScope $scope, int $limit): ?array
    {
        $today = CarbonImmutable::today();
        $threshold = $today->addDays((int) config('strategic_hr.command_center.document_window_days', 30))->toDateString();
        $union = null;

        foreach (self::DOCUMENT_SOURCES as $table => [$column, $kind]) {
            if (! InstalledTables::has($table)) {
                continue;
            }

            $part = DB::table($table)
                ->join('personnels', 'personnels.tabel_no', '=', "{$table}.tabel_no")
                ->whereNull('personnels.deleted_at')
                ->whereNotNull("{$table}.{$column}")
                ->where("{$table}.{$column}", '<=', $threshold)
                ->select([
                    "{$table}.id as id",
                    DB::raw("'{$kind}' as kind"),
                    "{$table}.{$column} as expires_on",
                    ...$this->nameColumns(),
                ]);
            $scope->constrain($part, 'personnels.structure_id');

            $union = $union === null ? $part : $union->unionAll($part);
        }

        if ($union === null) {
            return null;
        }

        $count = DB::query()->fromSub($union, 'expiring_docs')->count();
        $rows = $count === 0 ? collect() : DB::query()
            ->fromSub($union, 'expiring_docs')
            ->orderBy('expires_on')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return [
            'count' => $count,
            'rows' => $rows->map(function (object $row) use ($today): array {
                $expires = CarbonImmutable::parse($row->expires_on)->startOfDay();
                $expired = $expires->lt($today);

                return [
                    'id' => (int) $row->id,
                    'title' => $this->fullName($row),
                    'meta' => __('personnel::home.command.document_kinds.'.$row->kind).' · '.$expires->format('d.m.Y'),
                    'badge' => $expired
                        ? __('personnel::home.command.expired')
                        : __('personnel::home.command.in_days', ['days' => $this->daysBetween($today, $expires)]),
                    'tone' => $expired ? 'rose' : 'amber',
                ];
            })->values()->all(),
        ];
    }

    private function constrain(Builder $query, StructureScope $scope): void
    {
        $query->whereNull('personnels.deleted_at');
        $scope->constrain($query, 'personnels.structure_id');
    }

    /**
     * @return list<string>
     */
    private function nameColumns(): array
    {
        return ['personnels.surname', 'personnels.name', 'personnels.patronymic'];
    }

    private function fullName(object $row): string
    {
        return trim(implode(' ', array_filter([$row->surname ?? null, $row->name ?? null, $row->patronymic ?? null])));
    }

    private function daysBetween(mixed $from, CarbonImmutable $to): int
    {
        $start = $from instanceof CarbonImmutable ? $from : CarbonImmutable::parse((string) $from);

        return max(0, (int) $start->startOfDay()->diffInDays($to->startOfDay()));
    }
}
