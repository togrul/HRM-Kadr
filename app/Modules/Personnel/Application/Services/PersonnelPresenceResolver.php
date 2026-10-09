<?php

namespace App\Modules\Personnel\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\AttendanceCalendar;
use App\Models\Leave;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Modules\Personnel\Support\Presence\PersonnelPresence;
use App\Modules\Personnel\Support\Presence\PersonnelPresenceStatus;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * The one place that answers "where is this employee today?" — for the employee list,
 * the profile header and the home page alike.
 *
 * Absence data is read through the shared `App\Models` tables (personnel_vacations,
 * personnel_business_trips, leaves + leave_types, attendance_calendars), the same seam
 * HomeOverviewService and the absence sources use, so no module boundary is crossed.
 *
 * Precedence (strongest first, see PersonnelPresenceStatus):
 *   deleted > dismissed > pending > sick > vacation > business_trip > leave > at_work
 *
 * Coverage rules for the day D:
 * - vacation: approved or HR-entered (approval_status NULL / 'approved'), start_date <= D
 *   and end_date >= D (rows without end_date fall back to return_work_date > D);
 * - business trip: approved or HR-entered, start_date <= D <= end_date;
 * - leave / sick: status APPROVED, duration_unit 'day' (hourly and half-day permissions never
 *   flip the whole day), starts_at <= D <= ends_at; a leave type is "sick" when its
 *   attendance code or name says so (SICK_CODES / SICK_NAME_FRAGMENTS).
 *
 * Expected return = the vacation's own return_work_date when HR recorded one, otherwise the
 * first working day after the period ends on the organisation calendar (attendance_calendars,
 * global scope: holiday / weekend skipped, a moved workday counts; unmarked days fall back
 * to Mon–Fri).
 *
 * Cost: a page of people resolves in at most four queries (vacations, trips, leaves and —
 * only when someone is away — the calendar), whatever the page size.
 */
class PersonnelPresenceResolver
{
    /** Attendance codes that mark a sick-leave type. */
    public const SICK_CODES = ['XST', 'XS', 'X', 'SICK'];

    /** Lower-case fragments of a sick-leave type name (az / en / ru). */
    public const SICK_NAME_FRAGMENTS = ['xəstə', 'sick', 'ольнич'];

    /** Days of calendar read past the latest period end to find the next working day. */
    private const CALENDAR_LOOKAHEAD_DAYS = 31;

    public function resolve(Personnel $personnel, ?CarbonInterface $date = null): PersonnelPresence
    {
        return $this->resolveMany([$personnel], $date)[(int) $personnel->getKey()];
    }

    /**
     * @param  array<int, int>  $personnelIds
     * @return array<int, PersonnelPresence> keyed by personnel id
     */
    public function resolveIds(array $personnelIds, ?CarbonInterface $date = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $personnelIds))));

        if ($ids === []) {
            return [];
        }

        return $this->resolveMany(
            Personnel::query()
                ->withTrashed()
                ->whereKey($ids)
                ->get(['id', 'tabel_no', 'leave_work_date', 'is_pending', 'deleted_at']),
            $date,
        );
    }

    /**
     * Resolve already-loaded people. The models need id, tabel_no, leave_work_date,
     * is_pending and deleted_at.
     *
     * @param  iterable<Personnel>  $people
     * @return array<int, PersonnelPresence> keyed by personnel id
     */
    public function resolveMany(iterable $people, ?CarbonInterface $date = null): array
    {
        $day = $this->day($date);
        $people = collect($people);

        $candidates = $people
            ->filter(fn (Personnel $personnel): bool => $this->recordStatus($personnel) === null)
            ->pluck('tabel_no')
            ->filter(fn ($tabelNo): bool => filled($tabelNo))
            ->map(fn ($tabelNo): string => (string) $tabelNo)
            ->unique()
            ->values()
            ->all();

        $sick = $leave = $vacations = $trips = [];

        if ($candidates !== []) {
            [$sick, $leave] = $this->leaveRanges($candidates, $day);
            $vacations = $this->vacationRanges($candidates, $day);
            $trips = $this->tripRanges($candidates, $day);
        }

        $resolved = [];
        $pendingReturn = [];

        foreach ($people as $personnel) {
            $id = (int) $personnel->getKey();
            $tabelNo = (string) $personnel->tabel_no;
            $status = $this->recordStatus($personnel);

            if ($status !== null) {
                $resolved[$id] = new PersonnelPresence($id, $tabelNo, $status, $status->label());

                continue;
            }

            [$status, $range] = match (true) {
                isset($sick[$tabelNo]) => [PersonnelPresenceStatus::Sick, $sick[$tabelNo]],
                isset($vacations[$tabelNo]) => [PersonnelPresenceStatus::Vacation, $vacations[$tabelNo]],
                isset($trips[$tabelNo]) => [PersonnelPresenceStatus::BusinessTrip, $trips[$tabelNo]],
                isset($leave[$tabelNo]) => [PersonnelPresenceStatus::Leave, $leave[$tabelNo]],
                default => [PersonnelPresenceStatus::AtWork, null],
            };

            if ($range === null) {
                $resolved[$id] = new PersonnelPresence($id, $tabelNo, $status, $status->label());

                continue;
            }

            $resolved[$id] = [$status, $range, $tabelNo];

            if ($range['return'] === null) {
                $pendingReturn[] = $range['end'];
            }
        }

        $calendar = $this->calendar($pendingReturn);

        foreach ($resolved as $id => $entry) {
            if ($entry instanceof PersonnelPresence) {
                continue;
            }

            [$status, $range, $tabelNo] = $entry;

            $resolved[$id] = new PersonnelPresence(
                personnelId: $id,
                tabelNo: $tabelNo,
                status: $status,
                reason: $range['reason'] ?? $status->label(),
                periodStart: $range['start'],
                periodEnd: $range['end'],
                expectedReturn: $range['return'] ?? $this->nextWorkingDay($range['end'], $calendar),
            );
        }

        return $resolved;
    }

    /**
     * Narrow a personnel query to the people whose resolved status for $date is one of
     * $statuses — in SQL, so the list never loads everyone into PHP to filter.
     *
     * @param  Builder<Personnel>  $query
     * @param  array<int, string|PersonnelPresenceStatus>  $statuses
     * @return Builder<Personnel>
     */
    public function constrainToStatuses(Builder $query, array $statuses, ?CarbonInterface $date = null): Builder
    {
        $statuses = $this->normalizeStatuses($statuses);

        if ($statuses === []) {
            return $query;
        }

        $day = $this->day($date);

        return $query->where(function (Builder $outer) use ($statuses, $day): void {
            foreach ($statuses as $status) {
                [$sql, $bindings] = $this->exclusiveCondition($status, $day);
                $outer->orWhereRaw('('.$sql.')', $bindings);
            }
        });
    }

    /**
     * How many rows of $query fall into each of $statuses on $date, in one query.
     *
     * @param  Builder<Personnel>  $query
     * @param  array<int, string|PersonnelPresenceStatus>  $statuses
     * @return array<string, int> status value => count
     */
    public function countByStatus(Builder $query, array $statuses, ?CarbonInterface $date = null): array
    {
        $statuses = $this->normalizeStatuses($statuses);

        if ($statuses === []) {
            return [];
        }

        [$columns, $bindings] = $this->countColumns($statuses, $this->day($date));

        $row = (clone $query)->toBase()->selectRaw(implode(', ', $columns), $bindings)->first();

        $counts = [];
        foreach ($statuses as $status) {
            $counts[$status->value] = (int) ($row->{'presence_'.$status->value} ?? 0);
        }

        return $counts;
    }

    /**
     * `SUM(CASE ...)` columns (aliased presence_<status>) for callers that fold the
     * presence tallies into a wider aggregate query.
     *
     * @param  array<int, string|PersonnelPresenceStatus>  $statuses
     * @return array{0: list<string>, 1: list<mixed>}
     */
    public function countColumns(array $statuses, ?CarbonInterface $date = null, ?string $scopeSql = null): array
    {
        $day = $this->day($date);
        $columns = [];
        $bindings = [];

        foreach ($this->normalizeStatuses($statuses) as $status) {
            [$sql, $statusBindings] = $this->exclusiveCondition($status, $day);
            $condition = $scopeSql !== null ? "({$scopeSql}) AND ({$sql})" : $sql;
            $columns[] = "SUM(CASE WHEN {$condition} THEN 1 ELSE 0 END) as presence_{$status->value}";
            array_push($bindings, ...$statusBindings);
        }

        return [$columns, $bindings];
    }

    /**
     * The SQL condition that holds exactly when $status is the person's resolved status:
     * its own fact holds and no stronger status applies.
     *
     * @return array{0: string, 1: list<mixed>}
     */
    public function exclusiveCondition(PersonnelPresenceStatus $status, CarbonImmutable $day): array
    {
        $predicates = $this->predicates($day);
        $parts = [];
        $bindings = [];

        foreach (PersonnelPresenceStatus::precedence() as $stronger) {
            if ($stronger === $status) {
                break;
            }

            [$sql, $strongerBindings] = $predicates[$stronger->value];
            $parts[] = "NOT ({$sql})";
            array_push($bindings, ...$strongerBindings);
        }

        if ($status !== PersonnelPresenceStatus::AtWork) {
            [$sql, $ownBindings] = $predicates[$status->value];
            $parts[] = "({$sql})";
            array_push($bindings, ...$ownBindings);
        }

        return [implode(' AND ', $parts), $bindings];
    }

    /**
     * @param  array<int, mixed>  $statuses
     * @return list<PersonnelPresenceStatus>
     */
    public function normalizeStatuses(array $statuses): array
    {
        $resolved = [];

        foreach ($statuses as $status) {
            $case = $status instanceof PersonnelPresenceStatus
                ? $status
                : (is_string($status) ? PersonnelPresenceStatus::tryFrom($status) : null);

            if ($case !== null) {
                $resolved[$case->value] = $case;
            }
        }

        // Keep precedence order so generated SQL is stable.
        return array_values(array_filter(
            PersonnelPresenceStatus::precedence(),
            fn (PersonnelPresenceStatus $status): bool => isset($resolved[$status->value]),
        ));
    }

    /** Record-level statuses that need no absence lookup. */
    private function recordStatus(Personnel $personnel): ?PersonnelPresenceStatus
    {
        return match (true) {
            $personnel->getAttribute('deleted_at') !== null => PersonnelPresenceStatus::Deleted,
            filled($personnel->getAttribute('leave_work_date')) => PersonnelPresenceStatus::Dismissed,
            (bool) $personnel->getAttribute('is_pending') => PersonnelPresenceStatus::Pending,
            default => null,
        };
    }

    /**
     * Raw SQL facts per status, on the `personnels` table.
     *
     * @return array<string, array{0: string, 1: list<mixed>}>
     */
    private function predicates(CarbonImmutable $day): array
    {
        [$dayStart, $dayEnd] = $this->bounds($day);
        $never = ['1 = 0', []];

        $fullDayLeave = 'SELECT 1 FROM leaves pl%s'
            .' WHERE pl.tabel_no = personnels.tabel_no'
            .' AND pl.deleted_at IS NULL'
            .' AND pl.status_id = ?'
            .' AND (pl.duration_unit IS NULL OR pl.duration_unit = ?)'
            .' AND pl.starts_at <= ? AND pl.ends_at >= ?';
        $leaveBindings = [OrderStatusEnum::APPROVED->value, 'day', $dayEnd, $dayStart];

        $sickCodes = implode(', ', array_fill(0, count(self::SICK_CODES), '?'));
        $sickNames = implode(' OR ', array_fill(0, count(self::SICK_NAME_FRAGMENTS), 'LOWER(plt.name) LIKE ?'));
        $sickBindings = [
            ...self::SICK_CODES,
            ...array_map(fn (string $fragment): string => '%'.$fragment.'%', self::SICK_NAME_FRAGMENTS),
        ];

        $hasLeaves = InstalledTables::has('leaves') && InstalledTables::has('leave_types');

        return [
            PersonnelPresenceStatus::Deleted->value => ['personnels.deleted_at IS NOT NULL', []],
            PersonnelPresenceStatus::Dismissed->value => ['personnels.leave_work_date IS NOT NULL', []],
            PersonnelPresenceStatus::Pending->value => ['COALESCE(personnels.is_pending, 0) = 1', []],
            PersonnelPresenceStatus::Sick->value => $hasLeaves
                ? [
                    'EXISTS ('.sprintf($fullDayLeave, ' INNER JOIN leave_types plt ON plt.id = pl.leave_type_id')
                        ." AND (UPPER(plt.attendance_code) IN ({$sickCodes}) OR {$sickNames}))",
                    [...$leaveBindings, ...$sickBindings],
                ]
                : $never,
            PersonnelPresenceStatus::Vacation->value => InstalledTables::has('personnel_vacations')
                ? [
                    'EXISTS (SELECT 1 FROM personnel_vacations pv'
                        .' WHERE pv.tabel_no = personnels.tabel_no'
                        .' AND pv.deleted_at IS NULL'
                        .' AND (pv.approval_status IS NULL OR pv.approval_status = ?)'
                        .' AND pv.start_date <= ?'
                        .' AND (pv.end_date >= ? OR (pv.end_date IS NULL AND pv.return_work_date > ?)))',
                    ['approved', $dayEnd, $dayStart, $dayEnd],
                ]
                : $never,
            PersonnelPresenceStatus::BusinessTrip->value => InstalledTables::has('personnel_business_trips')
                ? [
                    'EXISTS (SELECT 1 FROM personnel_business_trips pbt'
                        .' WHERE pbt.tabel_no = personnels.tabel_no'
                        .' AND pbt.deleted_at IS NULL'
                        .' AND (pbt.approval_status IS NULL OR pbt.approval_status = ?)'
                        .' AND pbt.start_date <= ? AND pbt.end_date >= ?)',
                    ['approved', $dayEnd, $dayStart],
                ]
                : $never,
            PersonnelPresenceStatus::Leave->value => $hasLeaves
                ? ['EXISTS ('.sprintf($fullDayLeave, '').')', $leaveBindings]
                : $never,
            PersonnelPresenceStatus::AtWork->value => ['1 = 1', []],
        ];
    }

    /**
     * Approved full-day leaves covering the day, split into sick and other leave; the
     * latest-ending one per person wins.
     *
     * @param  list<string>  $tabelNos
     * @return array{0: array<string, array<string, mixed>>, 1: array<string, array<string, mixed>>}
     */
    private function leaveRanges(array $tabelNos, CarbonImmutable $day): array
    {
        if (! InstalledTables::has('leaves') || ! InstalledTables::has('leave_types')) {
            return [[], []];
        }

        [$dayStart, $dayEnd] = $this->bounds($day);

        $rows = Leave::query()
            ->leftJoin('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
            ->whereIn('leaves.tabel_no', $tabelNos)
            ->where('leaves.status_id', OrderStatusEnum::APPROVED->value)
            ->where(fn ($query) => $query->whereNull('leaves.duration_unit')->orWhere('leaves.duration_unit', 'day'))
            ->where('leaves.starts_at', '<=', $dayEnd)
            ->where('leaves.ends_at', '>=', $dayStart)
            ->toBase()
            ->get(['leaves.tabel_no', 'leaves.starts_at', 'leaves.ends_at', 'leave_types.name as type_name', 'leave_types.attendance_code']);

        $sick = [];
        $other = [];

        foreach ($rows as $row) {
            $range = $this->range($row->starts_at, $row->ends_at);
            $isSick = $this->isSickType($row->attendance_code ?? null, $row->type_name ?? null);
            $range['reason'] = $isSick ? null : (filled($row->type_name ?? null) ? (string) $row->type_name : null);

            if ($isSick) {
                $sick = $this->keepLatest($sick, (string) $row->tabel_no, $range);
            } else {
                $other = $this->keepLatest($other, (string) $row->tabel_no, $range);
            }
        }

        return [$sick, $other];
    }

    /**
     * @param  list<string>  $tabelNos
     * @return array<string, array<string, mixed>>
     */
    private function vacationRanges(array $tabelNos, CarbonImmutable $day): array
    {
        if (! InstalledTables::has('personnel_vacations')) {
            return [];
        }

        [$dayStart, $dayEnd] = $this->bounds($day);

        $rows = PersonnelVacation::query()
            ->whereIn('tabel_no', $tabelNos)
            ->where(fn ($query) => $query->whereNull('approval_status')->orWhere('approval_status', 'approved'))
            ->where('start_date', '<=', $dayEnd)
            ->where(fn ($query) => $query
                ->where('end_date', '>=', $dayStart)
                ->orWhere(fn ($fallback) => $fallback->whereNull('end_date')->where('return_work_date', '>', $dayEnd)))
            ->toBase()
            ->get(['tabel_no', 'start_date', 'end_date', 'return_work_date']);

        $ranges = [];
        foreach ($rows as $row) {
            $return = filled($row->return_work_date) ? $this->date($row->return_work_date) : null;
            $end = filled($row->end_date) ? $row->end_date : $return?->subDay()->toDateString();
            $range = $this->range($row->start_date, $end);
            $range['return'] = $return !== null && $return->greaterThan($range['end']) ? $return : null;
            $ranges = $this->keepLatest($ranges, (string) $row->tabel_no, $range);
        }

        return $ranges;
    }

    /**
     * @param  list<string>  $tabelNos
     * @return array<string, array<string, mixed>>
     */
    private function tripRanges(array $tabelNos, CarbonImmutable $day): array
    {
        if (! InstalledTables::has('personnel_business_trips')) {
            return [];
        }

        [$dayStart, $dayEnd] = $this->bounds($day);

        $rows = PersonnelBusinessTrip::query()
            ->whereIn('tabel_no', $tabelNos)
            ->where(fn ($query) => $query->whereNull('approval_status')->orWhere('approval_status', 'approved'))
            ->where('start_date', '<=', $dayEnd)
            ->where('end_date', '>=', $dayStart)
            ->toBase()
            ->get(['tabel_no', 'start_date', 'end_date']);

        $ranges = [];
        foreach ($rows as $row) {
            $ranges = $this->keepLatest($ranges, (string) $row->tabel_no, $this->range($row->start_date, $row->end_date));
        }

        return $ranges;
    }

    public function isSickType(?string $attendanceCode, ?string $name): bool
    {
        if ($attendanceCode !== null && in_array(strtoupper(trim($attendanceCode)), self::SICK_CODES, true)) {
            return true;
        }

        $name = mb_strtolower((string) $name);

        foreach (self::SICK_NAME_FRAGMENTS as $fragment) {
            if ($name !== '' && str_contains($name, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, array<string, mixed>>  $ranges
     * @param  array<string, mixed>  $range
     * @return array<string, array<string, mixed>>
     */
    private function keepLatest(array $ranges, string $tabelNo, array $range): array
    {
        if (! isset($ranges[$tabelNo]) || $range['end']->greaterThan($ranges[$tabelNo]['end'])) {
            $ranges[$tabelNo] = $range;
        }

        return $ranges;
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable, return: CarbonImmutable|null, reason: string|null}
     */
    private function range(mixed $start, mixed $end): array
    {
        $startDate = $this->date($start);

        return [
            'start' => $startDate,
            'end' => filled($end) ? $this->date($end) : $startDate,
            'return' => null,
            'reason' => null,
        ];
    }

    /**
     * Global calendar marks for the window the return dates can fall in, in one query.
     *
     * @param  list<CarbonImmutable>  $periodEnds
     * @return array<string, string> Y-m-d => day_type
     */
    private function calendar(array $periodEnds): array
    {
        if ($periodEnds === [] || ! InstalledTables::has('attendance_calendars')) {
            return [];
        }

        $from = collect($periodEnds)->min()->addDay();
        $to = collect($periodEnds)->max()->addDays(self::CALENDAR_LOOKAHEAD_DAYS);

        return AttendanceCalendar::query()
            ->where('scope_type', 'global')
            ->where('date', '>=', $from->toDateString())
            ->where('date', '<=', $to->toDateString().' 23:59:59')
            ->toBase()
            ->get(['date', 'day_type'])
            ->mapWithKeys(fn (object $row): array => [substr((string) $row->date, 0, 10) => (string) $row->day_type])
            ->all();
    }

    /**
     * @param  array<string, string>  $calendar
     */
    private function nextWorkingDay(CarbonImmutable $periodEnd, array $calendar): CarbonImmutable
    {
        $day = $periodEnd;

        for ($step = 0; $step < self::CALENDAR_LOOKAHEAD_DAYS; $step++) {
            $day = $day->addDay();

            $working = match ($calendar[$day->toDateString()] ?? null) {
                'workday' => true,
                'holiday', 'weekend' => false,
                default => ! $day->isWeekend(),
            };

            if ($working) {
                return $day;
            }
        }

        return $periodEnd->addDay();
    }

    /**
     * Index-friendly day bounds that match both DATE columns and the `Y-m-d H:i:s`
     * strings SQLite stores for date casts.
     *
     * @return array{0: string, 1: string}
     */
    private function bounds(CarbonImmutable $day): array
    {
        return [$day->toDateString(), $day->toDateString().' 23:59:59'];
    }

    private function day(?CarbonInterface $date): CarbonImmutable
    {
        return $date === null
            ? CarbonImmutable::today()
            : CarbonImmutable::parse($date->toDateString());
    }

    private function date(mixed $value): CarbonImmutable
    {
        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::parse($value->toDateString());
        }

        return CarbonImmutable::parse(substr((string) $value, 0, 10));
    }
}
