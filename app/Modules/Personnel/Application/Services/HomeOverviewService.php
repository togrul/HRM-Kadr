<?php

namespace App\Modules\Personnel\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\AttendanceManualEntry;
use App\Models\AuditActivity;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Modules\Leaves\Contracts\SickCertificateAttention;
use App\Modules\Personnel\Application\Services\MyHr\MyHrRequestReviewReadService;
use App\Modules\Personnel\Support\Presence\PersonnelPresenceStatus;
use App\Modules\Staff\Contracts\StaffingLookup;
use App\Services\StructureScope;
use App\Services\StructureService;
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
        'personnel_identity_documents' => 'valid_date',
        'personnel_contracts' => 'contract_ends_at',
    ];

    /**
     * Seconds a slightly stale aggregate may be served from cache. Only blocks nobody
     * acts on from the landing page use it; the pending queues stay live.
     */
    private const CACHE_TTL_SECONDS = 60;

    /** Audit log channels that record access rather than work; left out of the feed. */
    private const ACCESS_LOGS = ['auth', 'personnel_access'];

    /**
     * The "needs attention" tiles, in the order the design lays them out. The long-open
     * sick certificates tile comes from the Leaves module (SickCertificateAttention) and
     * only exists while that module is enabled.
     *
     * Always live: these are the queues the viewer works off, so a count must drop the
     * moment they approve something and come back.
     *
     * @return list<array<string,mixed>>
     */
    public function attention(?Authorizable $viewer): array
    {
        $scope = $this->scopeOf($viewer);

        $tiles = [
            [
                'key' => 'attendance_pending',
                'permission' => 'show-attendance-manual',
                'route' => 'attendance.manual-entries',
                'accent' => 'amber',
                'stats' => fn (): array => $this->pendingManualEntries($scope),
            ],
            [
                'key' => 'unsigned_orders',
                'permission' => 'show-orders',
                'route' => 'orders',
                'accent' => 'rose',
                'stats' => fn (): array => $this->unsignedOrders($scope),
            ],
            [
                'key' => 'vacation_requests',
                'permission' => 'show-vacations',
                'route' => 'vacations.list',
                'accent' => 'green',
                'stats' => fn (): array => $this->pendingVacationRequests($scope),
            ],
            [
                'key' => 'expiring_documents',
                'permission' => 'show-document-compliance',
                'route' => 'document-compliance',
                'accent' => 'neutral',
                'stats' => fn (): array => $this->expiringDocuments($scope),
            ],
        ];

        // Uzun açıq vərəqələr modulun təşkilat üzrə sayıdır — yalnız bütün strukturları görənə.
        if (app()->bound(SickCertificateAttention::class) && $scope->isAll()) {
            $tiles[] = [
                'key' => 'stale_sick_certificates',
                'permission' => 'show-leaves',
                'route' => SickCertificateAttention::ROUTE,
                'params' => ['stale' => 1],
                'accent' => 'rose',
                'stats' => fn (): array => $this->staleSickCertificates(),
            ];
        }

        return collect($tiles)
            ->filter(fn (array $tile): bool => $this->can($viewer, $tile['permission']))
            ->map(fn (array $tile): array => [
                'key' => $tile['key'],
                'route' => $tile['route'],
                'params' => $tile['params'] ?? [],
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
            'attendance_pending' => $this->can($viewer, 'show-attendance-manual') ? $this->manualEntryItems($limit, $this->scopeOf($viewer)) : [],
            'unsigned_orders' => $this->can($viewer, 'show-orders') ? $this->unsignedOrderItems($limit, $this->scopeOf($viewer)) : [],
            'vacation_requests' => $this->can($viewer, 'show-vacations')
                ? app(MyHrRequestReviewReadService::class)->pendingVacationItems($viewer, $limit)
                : [],
            default => [],
        };
    }

    /**
     * Each row links to the manual-entries tab, so a viewer who may see but not approve
     * the entries still has somewhere to go (approvers get approve / reject instead).
     *
     * @return list<array{id:int,title:string,meta:string,url:string}>
     */
    private function manualEntryItems(int $limit, StructureScope $scope): array
    {
        if (! InstalledTables::has('attendance_manual_entries')) {
            return [];
        }

        $url = route('attendance.manual-entries');

        return $scope->constrainThrough(AttendanceManualEntry::query(), 'personnel')
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
                'url' => $url,
            ])
            ->all();
    }

    /**
     * @return list<array{id:int,title:string,meta:string,url:string}>
     */
    private function unsignedOrderItems(int $limit, StructureScope $scope): array
    {
        if (! InstalledTables::has('order_logs')) {
            return [];
        }

        return $this->scopedOrders($scope)
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
    private function pendingManualEntries(StructureScope $scope): array
    {
        if (! InstalledTables::has('attendance_manual_entries')) {
            return self::EMPTY_QUEUE;
        }

        return $this->queueStats($scope->constrainThrough(AttendanceManualEntry::query(), 'personnel')->where('approval_status', 'pending'));
    }

    /**
     * @return array{count:int,oldest_days:int|null}
     */
    private function unsignedOrders(StructureScope $scope): array
    {
        if (! InstalledTables::has('order_logs')) {
            return self::EMPTY_QUEUE;
        }

        return $this->queueStats($this->scopedOrders($scope)->where('status_id', OrderStatusEnum::PENDING->value));
    }

    /**
     * @return array{count:int,oldest_days:int|null}
     */
    private function pendingVacationRequests(StructureScope $scope): array
    {
        if (! InstalledTables::has('personnel_vacations')) {
            return self::EMPTY_QUEUE;
        }

        return $this->queueStats($scope->constrainThrough(PersonnelVacation::query(), 'personnel')->where('approval_status', 'pending'));
    }

    /**
     * Sick certificates open longer than the Leaves alert threshold; the age is that of
     * the oldest one, counted from its first sick day.
     *
     * @return array{count:int,oldest_days:int|null}
     */
    private function staleSickCertificates(): array
    {
        $stale = app(SickCertificateAttention::class)->staleOpen();

        return ['count' => (int) $stale['count'], 'oldest_days' => $stale['oldest_days']];
    }

    /**
     * @return array{count:int,oldest_days:int|null}
     */
    private function expiringDocuments(StructureScope $scope): array
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
                $scope->constrain(
                    DB::table($table)
                        ->selectRaw('COUNT(*)')
                        ->join('personnels', 'personnels.tabel_no', '=', "{$table}.tabel_no")
                        ->whereNull('personnels.deleted_at')
                        ->whereNotNull("{$table}.{$column}")
                        ->whereDate("{$table}.{$column}", '<=', $threshold),
                    'personnels.structure_id',
                ),
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
            $scope = $this->scopeOf($viewer);
            $birthdays = $this->remember('birthdays', fn (): array => $this->birthdaysToday($scope), $scope);

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
                'count' => $this->remember('vacations_starting', fn (): int => $this->vacationsStartingThisWeek($this->scopeOf($viewer)), $this->scopeOf($viewer)),
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
    private function birthdaysToday(StructureScope $scope): array
    {
        if (! InstalledTables::has('personnels')) {
            return ['count' => 0, 'names' => null];
        }

        $today = CarbonImmutable::today();

        $rows = $scope->constrain(DB::table('personnels'), 'structure_id')
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
    private function vacationsStartingThisWeek(StructureScope $scope): int
    {
        if (! InstalledTables::has('personnel_vacations')) {
            return 0;
        }

        $today = CarbonImmutable::today();

        return $scope->constrainThrough(PersonnelVacation::query(), 'personnel')
            ->whereBetween('start_date', [$today->toDateString(), $today->addDays(6)->toDateString()])
            ->where(fn ($query) => $query
                ->whereNull('approval_status')
                ->orWhereNotIn('approval_status', ['pending', 'rejected']))
            ->count();
    }

    /**
     * "Away today": employed people whose resolved status today is an absence (sick,
     * vacation, business trip, other leave), limited to the structures the viewer may see —
     * the same scope the employee list applies. Live (no cache): the scope is per viewer.
     *
     * Cost: one aggregate for the per-reason counts, one page of rows, then the resolver's
     * three range reads and one calendar read for the return dates — constant in headcount.
     *
     * @return array{total:int,counts:array<string,int>,rows:list<array{id:int,name:string,status:string,label:string,reason:string,tone:string,returns:string|null,period:string|null}>,more:int}
     */
    public function absentToday(?Authorizable $viewer, int $limit = 6): array
    {
        $empty = ['total' => 0, 'counts' => [], 'rows' => [], 'more' => 0];

        if (! $this->can($viewer, 'show-personnels') || ! $viewer instanceof User || ! InstalledTables::has('personnels')) {
            return $empty;
        }

        $structures = app(StructureService::class)->getAccessibleStructures($viewer);

        if ($structures === []) {
            return $empty;
        }

        $resolver = app(PersonnelPresenceResolver::class);
        $absences = PersonnelPresenceStatus::absences();

        $base = Personnel::query()
            ->whereIn('personnels.structure_id', $structures)
            ->whereNull('personnels.leave_work_date')
            ->where('personnels.is_pending', false);
        $resolver->constrainToStatuses($base, $absences);

        $counts = array_filter($resolver->countByStatus($base, $absences), fn (int $count): bool => $count > 0);
        $total = array_sum($counts);

        if ($total === 0) {
            return $empty;
        }

        $people = (clone $base)
            ->orderBy('personnels.surname')
            ->orderBy('personnels.name')
            ->limit($limit)
            ->get(['personnels.id', 'personnels.tabel_no', 'personnels.surname', 'personnels.name', 'personnels.patronymic', 'personnels.leave_work_date', 'personnels.is_pending', 'personnels.deleted_at']);

        $presences = $resolver->resolveMany($people);

        $rows = $people->map(function (Personnel $personnel) use ($presences): array {
            $presence = $presences[(int) $personnel->id];

            return [
                'id' => (int) $personnel->id,
                'name' => trim($personnel->surname.' '.$personnel->name),
                'status' => $presence->status->value,
                'label' => $presence->label(),
                'reason' => $presence->reason,
                'tone' => $presence->tone(),
                'returns' => $presence->expectedReturnLabel(),
                'period' => $presence->periodLabel(),
            ];
        })->values()->all();

        return ['total' => $total, 'counts' => $counts, 'rows' => $rows, 'more' => max(0, $total - count($rows))];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function attendanceWeek(?Authorizable $viewer): array
    {
        return $this->can($viewer, 'show-attendance')
            ? $this->remember('attendance_week', fn (): array => $this->readAttendanceWeek($this->scopeOf($viewer)), $this->scopeOf($viewer))
            : [];
    }

    /**
     * @return list<array{id:int,event:string,subject:string,subject_id:int|null,actor:string,at:\Carbon\Carbon|null}>
     */
    public function activity(?Authorizable $viewer): array
    {
        // Audit lenti struktur üzrə süzülmür — yalnız bütün strukturları görənə göstərilir.
        return $this->can($viewer, 'show-audit-logs') && $this->scopeOf($viewer)->isAll()
            ? $this->remember('activity', fn (): array => $this->recentActivity())
            : [];
    }

    /**
     * @return list<array{id:int,name:string,total:int,filled:int,vacant:int,pct:int}>
     */
    public function structureFill(?Authorizable $viewer): array
    {
        return $this->can($viewer, 'show-staff')
            ? $this->remember('structure_fill', fn (): array => $this->readStructureFill($this->scopeOf($viewer)), $this->scopeOf($viewer))
            : [];
    }

    /**
     * Present / absent totals for the last seven days, read from the pre-aggregated
     * daily structure summary so the chart costs a single grouped query.
     *
     * @return list<array<string,mixed>>
     */
    private function readAttendanceWeek(StructureScope $scope): array
    {
        if (! InstalledTables::has('attendance_daily_structure_summaries')) {
            return [];
        }

        $today = CarbonImmutable::today();
        $start = $today->subDays(6);

        $rows = $scope->constrain(DB::table('attendance_daily_structure_summaries'), 'structure_id')
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
            // Sign-ins and profile views are access logs, not work: they drowned out
            // the orders, leaves and edits the feed is meant to show.
            ->whereNotIn('log_name', self::ACCESS_LOGS)
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
    private function readStructureFill(StructureScope $scope, int $limit = 6): array
    {
        if (! InstalledTables::has('staff_schedules') || ! InstalledTables::has('structures')) {
            return [];
        }

        // Dolu comes from the live headcount (the stored staff_schedules.filled counter
        // drifts), credited per ştat row only up to its own total.
        $fill = array_filter(
            app(StaffingLookup::class)->structureFill(),
            fn (array $row, int|string $structureId): bool => $row['total'] > 0 && $scope->allows($structureId),
            ARRAY_FILTER_USE_BOTH,
        );
        if ($fill === []) {
            return [];
        }

        uasort($fill, fn (array $a, array $b): int => $b['total'] <=> $a['total']);
        $fill = array_slice($fill, 0, $limit, true);
        $names = DB::table('structures')->whereIn('id', array_keys($fill))->pluck('name', 'id');

        $result = [];
        foreach ($fill as $id => $row) {
            $result[] = [
                'id' => (int) $id,
                'name' => (string) ($names[$id] ?? ''),
                'total' => $row['total'],
                'filled' => $row['filled'],
                'vacant' => $row['total'] - $row['filled'],
                'pct' => (int) round(($row['filled'] / $row['total']) * 100),
            ];
        }

        return $result;
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
    private function remember(string $block, Closure $resolver, ?StructureScope $scope = null): mixed
    {
        // Struktur görünürlüyü fərqli olan baxanlar eyni keşi bölüşmür.
        $scopeKey = $scope === null ? 'org' : ($scope->isAll() ? 'all' : md5(implode(',', $scope->ids())));

        return Cache::remember(
            'home:overview:'.$block.':'.$scopeKey.':'.CarbonImmutable::today()->toDateString(),
            self::CACHE_TTL_SECONDS,
            $resolver,
        );
    }

    private function scopeOf(?Authorizable $viewer): StructureScope
    {
        return $viewer instanceof User ? app(StructureService::class)->scopeFor($viewer) : StructureScope::none();
    }

    /**
     * Əmr görünürlüyü: bütün işçiləri görünürlükdə olan əmrlər (Orders modulunun qaydası).
     * Məhdud baxana işçisiz (işə qəbul) əmrlər göstərilmir.
     *
     * @return EloquentBuilder<OrderLog>
     */
    private function scopedOrders(StructureScope $scope): EloquentBuilder
    {
        $query = OrderLog::query();

        if ($scope->isAll()) {
            return $query;
        }

        if ($scope->isNone()) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('personnels', fn ($personnel) => $personnel->whereIn('personnels.structure_id', $scope->ids()))
            ->whereDoesntHave('personnels', fn ($personnel) => $personnel->where(fn ($outside) => $outside
                ->whereNull('personnels.structure_id')
                ->orWhereNotIn('personnels.structure_id', $scope->ids())));
    }

    private function can(?Authorizable $viewer, string $permission): bool
    {
        return $viewer?->can($permission) === true;
    }
}
