<?php

namespace App\Modules\Personnel\Application\Services;

use App\Models\AttendanceManualEntry;
use App\Models\AuditActivity;
use App\Models\OrderLog;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Services\Orders\Document\OrderIssueService;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Landing dashboard reads. Every block is permission-gated and skipped entirely
 * when the viewer cannot see it, so an unprivileged home page costs no queries.
 *
 * Cross-module data is read through the shared `App\Models` tables only — the same
 * seam ReportsOverviewService uses — so no module boundary is crossed.
 */
class HomeOverviewService
{
    /** Documents inside this window (or already expired) count as "expiring". */
    private const EXPIRY_WINDOW_DAYS = 30;

    /** A queue whose table is not installed on this deployment. */
    private const EMPTY_QUEUE = ['count' => 0, 'oldest_days' => null];

    /** Personnel document tables and the column holding their expiry date. */
    private const EXPIRY_SOURCES = [
        'personnel_cards' => 'valid_date',
        'personnel_passports' => 'valid_date',
        'personnel_contracts' => 'contract_ends_at',
    ];

    /**
     * @return array<string,mixed>
     */
    public function payload(?Authorizable $viewer): array
    {
        $attention = $this->attention($viewer);

        return [
            'attention' => $attention,
            'today' => $this->today($viewer, $attention),
            'attendance_week' => $this->can($viewer, 'show-attendance') ? $this->attendanceWeek() : [],
            'activity' => $this->can($viewer, 'show-audit-logs') ? $this->recentActivity() : [],
            'structure_fill' => $this->can($viewer, 'show-staff') ? $this->structureFill() : [],
        ];
    }

    /**
     * The four "needs attention" tiles, in the order the design lays them out.
     *
     * @return list<array<string,mixed>>
     */
    private function attention(?Authorizable $viewer): array
    {
        $tiles = [
            [
                'key' => 'attendance_pending',
                'permission' => 'show-attendance-manual',
                'route' => 'attendance.manual-entries',
                'accent' => 'amber',
                'stats' => fn (): array => $this->pendingManualEntries(),
            ],
            [
                'key' => 'unsigned_orders',
                'permission' => 'show-orders',
                'route' => 'orders',
                'accent' => 'rose',
                'stats' => fn (): array => $this->unsignedOrders(),
            ],
            [
                'key' => 'vacation_requests',
                'permission' => 'show-vacations',
                'route' => 'vacations.list',
                'accent' => 'green',
                'stats' => fn (): array => $this->pendingVacationRequests(),
            ],
            [
                'key' => 'expiring_documents',
                'permission' => 'show-document-compliance',
                'route' => 'document-compliance',
                'accent' => 'neutral',
                'stats' => fn (): array => $this->expiringDocuments(),
            ],
        ];

        return collect($tiles)
            ->filter(fn (array $tile): bool => $this->can($viewer, $tile['permission']))
            ->map(fn (array $tile): array => [
                'key' => $tile['key'],
                'route' => $tile['route'],
                'accent' => $tile['accent'],
                ...($tile['stats'])(),
            ])
            ->values()
            ->all();
    }

    /**
     * Count and age a pending queue in one round trip — the tiles show both numbers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return array{count:int,oldest_days:int|null}
     */
    private function queueStats(EloquentBuilder $query): array
    {
        $row = $query->toBase()->selectRaw('COUNT(*) as aggregate, MIN(created_at) as oldest')->first();
        $count = (int) ($row->aggregate ?? 0);
        $oldest = $row?->oldest;

        return [
            'count' => $count,
            'oldest_days' => $count > 0 && $oldest
                ? (int) CarbonImmutable::parse($oldest)->startOfDay()->diffInDays(CarbonImmutable::today())
                : null,
        ];
    }

    /**
     * @return array{count:int,oldest_days:int|null}
     */
    private function pendingManualEntries(): array
    {
        if (! InstalledTables::has('attendance_manual_entries')) {
            return self::EMPTY_QUEUE;
        }

        return $this->queueStats(AttendanceManualEntry::query()->where('approval_status', 'pending'));
    }

    /**
     * @return array{count:int,oldest_days:int|null}
     */
    private function unsignedOrders(): array
    {
        if (! InstalledTables::has('order_logs')) {
            return self::EMPTY_QUEUE;
        }

        return $this->queueStats(OrderLog::query()->where('status_id', OrderIssueService::STATUS_PENDING));
    }

    /**
     * @return array{count:int,oldest_days:int|null}
     */
    private function pendingVacationRequests(): array
    {
        if (! InstalledTables::has('personnel_vacations')) {
            return self::EMPTY_QUEUE;
        }

        return $this->queueStats(PersonnelVacation::query()->where('approval_status', 'pending'));
    }

    /**
     * @return array{count:int,oldest_days:int|null}
     */
    private function expiringDocuments(): array
    {
        $threshold = CarbonImmutable::today()->addDays(self::EXPIRY_WINDOW_DAYS)->toDateString();
        $total = 0;

        foreach (self::EXPIRY_SOURCES as $table => $column) {
            if (! InstalledTables::has($table) || ! InstalledTables::has('personnels')) {
                continue;
            }

            $total += DB::table($table)
                ->join('personnels', 'personnels.tabel_no', '=', "{$table}.tabel_no")
                ->whereNull('personnels.deleted_at')
                ->whereNotNull("{$table}.{$column}")
                ->whereDate("{$table}.{$column}", '<=', $threshold)
                ->count();
        }

        return ['count' => $total, 'oldest_days' => null];
    }

    /**
     * The "today" rail: the queues that already came back from the tiles, plus the two
     * date-bound facts a landing page is actually asked for every morning.
     *
     * @param  list<array<string,mixed>>  $attention
     * @return list<array{key:string,count:int,accent:string,note:string|null,route:string|null}>
     */
    private function today(?Authorizable $viewer, array $attention): array
    {
        $rows = [];

        foreach ($attention as $tile) {
            if (! in_array($tile['key'], ['attendance_pending', 'unsigned_orders'], true)) {
                continue;
            }

            $rows[] = [
                'key' => (string) $tile['key'],
                'count' => (int) $tile['count'],
                'accent' => (string) $tile['accent'],
                'note' => null,
                'route' => (string) $tile['route'],
            ];
        }

        if ($this->can($viewer, 'show-personnels')) {
            $birthdays = $this->birthdaysToday();

            $rows[] = [
                'key' => 'birthdays',
                'count' => $birthdays['count'],
                'accent' => 'sky',
                'note' => $birthdays['names'],
                'route' => 'personnel.index',
            ];
        }

        if ($this->can($viewer, 'show-vacations')) {
            $rows[] = [
                'key' => 'vacations_starting',
                'count' => $this->vacationsStartingThisWeek(),
                'accent' => 'green',
                'note' => null,
                'route' => 'vacations.list',
            ];
        }

        return array_values(array_filter($rows, fn (array $row): bool => $row['count'] > 0));
    }

    /**
     * Birthdays are a handful of rows a day, so they are read whole and counted in PHP
     * rather than paying for a second COUNT round trip.
     *
     * ponytail: an unindexed month/day scan — add a generated birth-day column if the
     * personnel table ever outgrows a full scan on the landing page.
     *
     * @return array{count:int,names:string|null}
     */
    private function birthdaysToday(): array
    {
        if (! InstalledTables::has('personnels')) {
            return ['count' => 0, 'names' => null];
        }

        $today = CarbonImmutable::today();

        $rows = DB::table('personnels')
            ->whereNull('deleted_at')
            ->whereMonth('birthdate', $today->month)
            ->whereDay('birthdate', $today->day)
            ->orderBy('surname')
            ->get(['surname', 'name']);

        $names = $rows
            ->take(3)
            ->map(fn (object $row): string => trim($row->surname.' '.mb_substr((string) $row->name, 0, 1).'.'))
            ->implode(', ');

        return ['count' => $rows->count(), 'names' => $names !== '' ? $names : null];
    }

    /**
     * Approved leaves opening inside the next seven days. Legacy rows carry no
     * approval status at all, which historically meant "entered by HR" — approved.
     */
    private function vacationsStartingThisWeek(): int
    {
        if (! InstalledTables::has('personnel_vacations')) {
            return 0;
        }

        $today = CarbonImmutable::today();

        return PersonnelVacation::query()
            ->whereBetween('start_date', [$today->toDateString(), $today->addDays(6)->toDateString()])
            ->where(fn ($query) => $query
                ->whereNull('approval_status')
                ->orWhereNotIn('approval_status', ['pending', 'rejected']))
            ->count();
    }

    /**
     * Present / absent totals for the last seven days, read from the pre-aggregated
     * daily structure summary so the chart costs a single grouped query.
     *
     * @return list<array<string,mixed>>
     */
    private function attendanceWeek(): array
    {
        if (! InstalledTables::has('attendance_daily_structure_summaries')) {
            return [];
        }

        $today = CarbonImmutable::today();
        $start = $today->subDays(6);

        $rows = DB::table('attendance_daily_structure_summaries')
            ->selectRaw('date, SUM(present_days) as present, SUM(absence_days) as absent, SUM(scheduled_days) as scheduled')
            ->whereBetween('date', [$start->toDateString(), $today->endOfDay()->toDateTimeString()])
            ->groupBy('date')
            ->get()
            ->keyBy(fn (object $row): string => CarbonImmutable::parse($row->date)->toDateString());

        return collect(range(0, 6))
            ->map(function (int $offset) use ($start, $rows): array {
                $day = $start->addDays($offset);
                $row = $rows->get($day->toDateString());

                return [
                    'date' => $day->toDateString(),
                    'weekday' => $day->dayOfWeekIso,
                    'present' => (int) ($row->present ?? 0),
                    'absent' => (int) ($row->absent ?? 0),
                    'scheduled' => (int) ($row->scheduled ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id:int,event:string,subject:string,subject_id:int|null,actor:string,at:\Carbon\Carbon|null}>
     */
    private function recentActivity(int $limit = 8): array
    {
        $activities = AuditActivity::query()
            ->select(['id', 'log_name', 'event', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'created_at'])
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();

        $names = $this->userNames($activities);

        return $activities->map(function (AuditActivity $activity) use ($names): array {
            // Sign-in entries record the account as both actor and subject, so the
            // subject is dropped instead of repeating the same name after the verb.
            $subjectIsActor = $activity->subject_type === $activity->causer_type
                && (int) $activity->subject_id === (int) $activity->causer_id;

            return [
                'id' => (int) $activity->id,
                'event' => (string) ($activity->event ?? ''),
                'subject' => $subjectIsActor ? '' : class_basename((string) $activity->subject_type),
                'subject_id' => $subjectIsActor ? null : $activity->subject_id,
                'actor' => (string) ($names[(int) $activity->causer_id] ?? ''),
                'at' => $activity->created_at,
            ];
        })->values()->all();
    }

    /**
     * Activity logs may live on their own database connection, so causers are read
     * with a plain query rather than a morph eager-load, which would look for
     * `users` on the audit connection. Deleted accounts stay named in history.
     *
     * @param  Collection<int,AuditActivity>  $activities
     * @return array<int,string>
     */
    private function userNames(Collection $activities): array
    {
        $ids = $activities
            ->filter(fn (AuditActivity $activity): bool => $activity->causer_type === User::class)
            ->pluck('causer_id')
            ->filter()
            ->unique()
            ->values();

        return $ids->isEmpty()
            ? []
            : User::withTrashed()->whereKey($ids)->pluck('name', 'id')->all();
    }

    /**
     * Headcount coverage per structure, largest establishments first — those are
     * the ones whose gaps matter most on a landing page.
     *
     * @return list<array{id:int,name:string,total:int,filled:int,vacant:int,pct:int}>
     */
    private function structureFill(int $limit = 6): array
    {
        if (! InstalledTables::has('staff_schedules') || ! InstalledTables::has('structures')) {
            return [];
        }

        return DB::table('staff_schedules')
            ->join('structures', 'structures.id', '=', 'staff_schedules.structure_id')
            ->selectRaw('structures.id, structures.name, SUM(staff_schedules.total) as total, SUM(staff_schedules.filled) as filled')
            ->groupBy('structures.id', 'structures.name')
            ->havingRaw('SUM(staff_schedules.total) > 0')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function (object $row): array {
                $total = (int) $row->total;
                $filled = min((int) $row->filled, $total);

                return [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'total' => $total,
                    'filled' => $filled,
                    'vacant' => $total - $filled,
                    'pct' => $total > 0 ? (int) round(($filled / $total) * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function can(?Authorizable $viewer, string $permission): bool
    {
        return $viewer?->can($permission) === true;
    }
}
