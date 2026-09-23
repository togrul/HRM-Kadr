<?php

namespace App\Modules\Personnel\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\AttendanceManualEntry;
use App\Models\AuditActivity;
use App\Models\OrderLog;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Modules\Personnel\Application\Services\MyHr\MyHrRequestReviewReadService;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Landing dashboard reads. Every block is permission-gated and skipped entirely
 * when the viewer cannot see it, so an unprivileged home page costs no queries.
 *
 * Freshness per block:
 * - attention tiles (pending queues, expiring documents): live, the viewer acts on them;
 * - today rail: queue rows reuse the live tiles, birthdays / leaves starting are cached;
 * - attendance week, recent activity, structure coverage: cached for a minute and
 *   rendered in lazy islands, so they never hold up the first paint.
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
     * Seconds a slightly stale aggregate may be served from cache. Only blocks nobody
     * acts on from the landing page use it; the pending queues stay live.
     */
    private const CACHE_TTL_SECONDS = 60;

    /**
     * The four "needs attention" tiles, in the order the design lays them out.
     *
     * Always live: these are the queues the viewer works off, so a count must drop the
     * moment they approve something and come back.
     *
     * @return list<array<string,mixed>>
     */
    public function attention(?Authorizable $viewer): array
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
     * The first few rows behind an attention tile, oldest first — what the viewer works
     * through without leaving the home page. Same permission gates as the tiles.
     *
     * @return list<array{id:int,title:string,meta:string,url?:string}>
     */
    public function queueItems(string $key, User $viewer, int $limit = 5): array
    {
        return match ($key) {
            'attendance_pending' => $this->can($viewer, 'show-attendance-manual') ? $this->manualEntryItems($limit) : [],
            'unsigned_orders' => $this->can($viewer, 'show-orders') ? $this->unsignedOrderItems($limit) : [],
            'vacation_requests' => $this->can($viewer, 'show-vacations')
                ? app(MyHrRequestReviewReadService::class)->pendingVacationItems($viewer, $limit)
                : [],
            default => [],
        };
    }

    /**
     * @return list<array{id:int,title:string,meta:string}>
     */
    private function manualEntryItems(int $limit): array
    {
        if (! InstalledTables::has('attendance_manual_entries')) {
            return [];
        }

        return AttendanceManualEntry::query()
            ->with('personnel:tabel_no,surname,name,patronymic')
            ->where('approval_status', 'pending')
            ->oldest('date')
            ->oldest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AttendanceManualEntry $entry): array => [
                'id' => (int) $entry->id,
                'title' => $entry->personnel?->fullname ?: (string) $entry->tabel_no,
                'meta' => collect([
                    optional($entry->date)->format('d.m.Y'),
                    $entry->check_in_at && $entry->check_out_at
                        ? substr((string) $entry->check_in_at, 0, 5).'–'.substr((string) $entry->check_out_at, 0, 5)
                        : $entry->absence_code,
                    $entry->reason ? Str::limit((string) $entry->reason, 40) : null,
                ])->filter()->implode(' · '),
            ])
            ->all();
    }

    /**
     * @return list<array{id:int,title:string,meta:string,url:string}>
     */
    private function unsignedOrderItems(int $limit): array
    {
        if (! InstalledTables::has('order_logs')) {
            return [];
        }

        return OrderLog::query()
            ->select(['id', 'order_no', 'given_date', 'description', 'template_snapshot'])
            ->where('status_id', OrderStatusEnum::PENDING->value)
            ->oldest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (OrderLog $order): array => [
                'id' => (int) $order->id,
                'title' => '№ '.$order->order_no,
                'meta' => collect([
                    data_get($order->template_snapshot, 'label') ?: Str::limit((string) $order->description, 40),
                    $order->given_date ? CarbonImmutable::parse($order->given_date)->format('d.m.Y') : null,
                ])->filter()->implode(' · '),
                'url' => route('orders', ['search' => ['order_no' => $order->order_no]]),
            ])
            ->all();
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

        return $this->queueStats(OrderLog::query()->where('status_id', OrderStatusEnum::PENDING->value));
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
        if (! InstalledTables::has('personnels')) {
            return ['count' => 0, 'oldest_days' => null];
        }

        $threshold = CarbonImmutable::today()->addDays(self::EXPIRY_WINDOW_DAYS)->toDateString();
        $query = DB::query();
        $sources = 0;

        // One round trip for every document table instead of a COUNT each.
        foreach (self::EXPIRY_SOURCES as $table => $column) {
            if (! InstalledTables::has($table)) {
                continue;
            }

            $query->selectSub(
                DB::table($table)
                    ->selectRaw('COUNT(*)')
                    ->join('personnels', 'personnels.tabel_no', '=', "{$table}.tabel_no")
                    ->whereNull('personnels.deleted_at')
                    ->whereNotNull("{$table}.{$column}")
                    ->whereDate("{$table}.{$column}", '<=', $threshold),
                $table,
            );
            $sources++;
        }

        $total = $sources === 0 ? 0 : (int) array_sum(array_map('intval', (array) $query->first()));

        return ['count' => $total, 'oldest_days' => null];
    }

    /**
     * The "today" rail: the queues that already came back from the tiles, plus the two
     * date-bound facts a landing page is actually asked for every morning.
     *
     * The queue rows reuse the live tiles; birthdays and upcoming leaves are cached
     * per day because nothing on this page changes them.
     *
     * @param  list<array<string,mixed>>  $attention
     * @return list<array{key:string,count:int,accent:string,note:string|null,route:string|null}>
     */
    public function today(?Authorizable $viewer, array $attention): array
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
            $birthdays = $this->remember('birthdays', fn (): array => $this->birthdaysToday());

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
                'count' => $this->remember('vacations_starting', fn (): int => $this->vacationsStartingThisWeek()),
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
     * @return list<array<string,mixed>>
     */
    public function attendanceWeek(?Authorizable $viewer): array
    {
        return $this->can($viewer, 'show-attendance')
            ? $this->remember('attendance_week', fn (): array => $this->readAttendanceWeek())
            : [];
    }

    /**
     * @return list<array{id:int,event:string,subject:string,subject_id:int|null,actor:string,at:\Carbon\Carbon|null}>
     */
    public function activity(?Authorizable $viewer): array
    {
        return $this->can($viewer, 'show-audit-logs')
            ? $this->remember('activity', fn (): array => $this->recentActivity())
            : [];
    }

    /**
     * @return list<array{id:int,name:string,total:int,filled:int,vacant:int,pct:int}>
     */
    public function structureFill(?Authorizable $viewer): array
    {
        return $this->can($viewer, 'show-staff')
            ? $this->remember('structure_fill', fn (): array => $this->readStructureFill())
            : [];
    }

    /**
     * Present / absent totals for the last seven days, read from the pre-aggregated
     * daily structure summary so the chart costs a single grouped query.
     *
     * @return list<array<string,mixed>>
     */
    private function readAttendanceWeek(): array
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
    private function readStructureFill(int $limit = 6): array
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

    /**
     * Cached blocks read organisation-wide tables with no per-viewer scoping, so every
     * viewer who passes the permission gate (checked before this call) gets the same
     * answer; the key therefore carries the date, not the user.
     *
     * @template T
     *
     * @param  Closure():T  $resolver
     * @return T
     */
    private function remember(string $block, Closure $resolver): mixed
    {
        return Cache::remember(
            'home:overview:'.$block.':'.CarbonImmutable::today()->toDateString(),
            self::CACHE_TTL_SECONDS,
            $resolver,
        );
    }

    private function can(?Authorizable $viewer, string $permission): bool
    {
        return $viewer?->can($permission) === true;
    }
}
