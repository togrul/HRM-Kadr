<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceCalendar;
use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceShift;
use App\Models\AttendanceShiftAssignment;
use App\Models\Personnel;
use App\Models\Structure;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendancePuantajReadService
{
    public function paginatePersonnels(
        string $search,
        int $perPage,
        array $structureIds = [],
        ?Carbon $from = null,
        ?Carbon $to = null
    ): LengthAwarePaginator {
        $fromDate = $from?->toDateString();
        $toDate = $to?->toDateString();

        return Personnel::query()
            ->select([
                'id',
                'tabel_no',
                'surname',
                'name',
                'patronymic',
                'structure_id',
                'join_work_date',
                'leave_work_date',
                'is_pending',
            ])
            ->where('is_pending', 0)
            ->when($toDate !== null, fn ($query) => $query->whereDate('join_work_date', '<=', $toDate))
            ->when($fromDate !== null, function ($query) use ($fromDate): void {
                $query->where(function ($inner) use ($fromDate): void {
                    $inner->whereNull('leave_work_date')
                        ->orWhereDate('leave_work_date', '>=', $fromDate);
                });
            }, fn ($query) => $query->whereNull('leave_work_date'))
            ->when($structureIds !== [], fn ($query) => $query->whereIn('structure_id', $structureIds))
            ->when($search !== '', function ($query) use ($search): void {
                $wildcard = '%'.$search.'%';
                $query->where(function ($q) use ($wildcard): void {
                    $q->where('tabel_no', 'like', $wildcard)
                        ->orWhere('name', 'like', $wildcard)
                        ->orWhere('surname', 'like', $wildcard)
                        ->orWhere('patronymic', 'like', $wildcard);
                });
            })
            ->orderBy('surname')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @param  array<int,string>  $tabelNos
     * @return array<string,array<string,array<string,mixed>>>
     */
    public function loadLedgerMap(array $tabelNos, Carbon $from, Carbon $to): array
    {
        if ($tabelNos === []) {
            return [];
        }

        $ledgers = AttendanceDailyLedger::query()
            ->whereIn('tabel_no', $tabelNos)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['tabel_no', 'date', 'scheduled_minutes', 'worked_minutes', 'attendance_status', 'absence_code', 'meta']);

        return $ledgers
            ->groupBy('tabel_no')
            ->map(function (Collection $items): array {
                return $items->mapWithKeys(fn (AttendanceDailyLedger $ledger) => [
                    $ledger->date->toDateString() => [
                        'scheduled_minutes' => (int) $ledger->scheduled_minutes,
                        'worked_minutes' => (int) $ledger->worked_minutes,
                        'attendance_status' => (string) $ledger->attendance_status,
                        'absence_code' => (string) ($ledger->absence_code ?? ''),
                        'leave_type_id' => data_get($ledger->meta, 'leave_type_id'),
                        'leave_type_name' => (string) data_get($ledger->meta, 'leave_type_name', ''),
                        'leave_type_code' => trim((string) data_get($ledger->meta, 'leave_type_code', '')),
                        'calendar_day_type' => (string) data_get($ledger->meta, 'calendar_day_type', ''),
                        'duration_unit' => (string) data_get($ledger->meta, 'duration_unit', 'day'),
                        'partial_day_part' => data_get($ledger->meta, 'partial_day_part'),
                        'starts_time' => data_get($ledger->meta, 'starts_time'),
                        'ends_time' => data_get($ledger->meta, 'ends_time'),
                        'total_minutes' => data_get($ledger->meta, 'total_minutes'),
                        'covered_leave_minutes' => (int) data_get($ledger->meta, 'covered_leave_minutes', 0),
                    ],
                ])->all();
            })
            ->all();
    }

    /**
     * @param  array<int,int>  $structureIds
     * @return array<int,array<string,mixed>>
     */
    public function calendarOverrides(Carbon $from, Carbon $to, array $structureIds = []): array
    {
        $structureNames = $structureIds === []
            ? []
            : Structure::query()
                ->whereIn('id', $structureIds)
                ->pluck('name', 'id')
                ->all();

        return AttendanceCalendar::query()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->where(function ($query) use ($structureIds): void {
                $query->where('scope_type', 'global');

                if ($structureIds !== []) {
                    $query->orWhere(function ($q) use ($structureIds): void {
                        $q->where('scope_type', 'structure')
                            ->whereIn('scope_id', $structureIds);
                    });
                }
            })
            ->orderBy('date')
            ->orderBy('scope_type')
            ->get(['date', 'day_type', 'name', 'is_paid', 'scope_type', 'scope_id'])
            ->map(function (AttendanceCalendar $calendar) use ($structureNames): array {
                return [
                    'date' => $calendar->date?->toDateString(),
                    'day_type' => (string) $calendar->day_type,
                    'name' => (string) ($calendar->name ?? ''),
                    'is_paid' => (bool) $calendar->is_paid,
                    'scope_type' => (string) $calendar->scope_type,
                    'scope_label' => $calendar->scope_type === 'structure'
                        ? ($structureNames[(int) $calendar->scope_id] ?? ('#'.$calendar->scope_id))
                        : __('attendance::puantaj.calendar.global_scope'),
                ];
            })
            ->all();
    }

    /**
     * Gündəlik iş norması (dəqiqə) — legend və tam gün həddi üçün.
     */
    public function defaultDailyMinutes(): int
    {
        return app(AttendanceWorkNormService::class)->defaultDailyMinutes();
    }

    /**
     * Ledger sətri olmayan günlər üçün render zamanı hesablanan defolt (bazaya yazılmır).
     *
     * - Təsdiqlənmiş icazə, məzuniyyət və ezamiyyət günü öz statusu ilə göstərilir.
     * - Keçmiş iş günü (bu gündən əvvəl) qrafikə görə plan saatı ilə "planned" olur.
     * - Gələcək günlər və işə qəbuldan əvvəlki / işdən çıxdıqdan sonrakı günlər boş qalır.
     *
     * Bütün mənbələr toplu yüklənir (N+1 yoxdur): növbə təyinatları, defolt növbə,
     * təqvim istisnaları və yoxluq sənədləri.
     *
     * @param  iterable<int,Personnel>  $personnels
     * @param  array<string,array<string,array<string,mixed>>>  $ledgerMap
     * @return array<string,array<string,array<string,mixed>>>
     */
    public function loadScheduleDefaults(iterable $personnels, Carbon $from, Carbon $to, array $ledgerMap, ?CarbonInterface $today = null): array
    {
        $personnels = collect($personnels)->filter(fn ($personnel) => filled($personnel->tabel_no))->values();

        if ($personnels->isEmpty()) {
            return [];
        }

        $today = Carbon::parse(($today ?? Carbon::today())->toDateString())->startOfDay();
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $tabelNos = $personnels->pluck('tabel_no')->map(fn ($tabelNo) => (string) $tabelNo)->unique()->values();
        $structureByTabel = $personnels
            ->mapWithKeys(fn ($personnel) => [(string) $personnel->tabel_no => $personnel->structure_id !== null ? (int) $personnel->structure_id : null])
            ->all();

        $normService = app(AttendanceWorkNormService::class);
        $contextResolver = app(AttendanceDayContextResolverService::class);
        $context = $contextResolver->build($from, $to, $tabelNos, $structureByTabel);
        $hasPastDays = $from->lt($today);
        $assignmentsByTabel = $hasPastDays ? $this->shiftAssignmentsByTabel($tabelNos->all(), $from, $to) : [];
        $shiftLoad = $hasPastDays
            ? $normService->loadShiftsWithDefault(collect($assignmentsByTabel)->flatten(1)->pluck('shift_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all())
            : ['shifts' => [], 'default' => null];
        $shiftsById = $shiftLoad['shifts'];
        $defaultShift = $shiftLoad['default'];
        $defaultDailyMinutes = $defaultShift !== null
            ? $normService->shiftDailyMinutes($defaultShift)
            : AttendanceWorkNormService::DEFAULT_DAILY_MINUTES;
        $dailyMinutesByShift = [];
        // Qısaldılmış iş vaxtı (ƏM m.91–92) — yalnız plan saatı hesablanan keçmiş günlər üçün lazımdır.
        $profiles = $hasPastDays ? $normService->workingTimeProfiles($tabelNos->all()) : [];

        $defaults = [];

        foreach ($personnels as $personnel) {
            $tabelNo = (string) $personnel->tabel_no;
            $structureId = $structureByTabel[$tabelNo] ?? null;
            $joinDate = $personnel->join_work_date ? Carbon::parse($personnel->join_work_date)->startOfDay() : null;
            $leaveDate = $personnel->leave_work_date ? Carbon::parse($personnel->leave_work_date)->startOfDay() : null;

            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                $dateKey = $date->toDateString();

                if (isset($ledgerMap[$tabelNo][$dateKey])) {
                    continue;
                }

                if (($joinDate !== null && $date->lt($joinDate)) || ($leaveDate !== null && $date->gt($leaveDate))) {
                    continue;
                }

                $isPast = $date->lt($today);
                $override = $context['overrides'][$tabelNo.'|'.$dateKey] ?? null;

                if (! $isPast && $override === null) {
                    continue;
                }

                $dayType = $contextResolver->resolveCalendarDayType($date, $structureId, $context['calendars_global'], $context['calendars_structure']);
                $nextDayType = $contextResolver->resolveCalendarDayType($date->copy()->addDay(), $structureId, $context['calendars_global'], $context['calendars_structure']);
                $plannedMinutes = 0;
                $shortensBeforeHoliday = true;

                if ($isPast) {
                    $shift = $this->resolveShiftForDate($assignmentsByTabel[$tabelNo] ?? [], $date, $shiftsById, $defaultShift);
                    $dailyMinutes = $shift === null
                        ? $defaultDailyMinutes
                        : ($dailyMinutesByShift[(int) $shift->id] ??= $normService->shiftDailyMinutes($shift));
                    $profile = $profiles[$tabelNo] ?? null;
                    $dailyMinutes = $normService->personalDailyMinutes($dailyMinutes, $profile, $date);
                    $shortensBeforeHoliday = $normService->shortensBeforeHoliday($profile, $date);
                    $plannedMinutes = $normService->plannedMinutesForDay($dailyMinutes, $dayType, $nextDayType, $shortensBeforeHoliday);
                }

                $entry = $override !== null
                    ? $this->buildOverrideDefault($override, $dayType, $plannedMinutes, $isPast)
                    : ($plannedMinutes > 0 ? [
                        'scheduled_minutes' => $plannedMinutes,
                        'worked_minutes' => $plannedMinutes,
                        'attendance_status' => 'planned',
                        'absence_code' => '',
                        'calendar_day_type' => $dayType,
                    ] : null);

                if ($entry === null) {
                    continue;
                }

                $entry['is_default'] = true;
                $entry['pre_holiday'] = $plannedMinutes > 0 && $dayType === 'workday' && $nextDayType === 'holiday' && $shortensBeforeHoliday;
                $defaults[$tabelNo][$dateKey] = $entry;
            }
        }

        return $defaults;
    }

    /**
     * Sənəd üzrə yoxluğun hüceyrə görünüşü — pipeline-ın yazacağı ledger ilə eyni forma.
     *
     * @param  array<string,mixed>  $override
     * @return array<string,mixed>|null
     */
    private function buildOverrideDefault(array $override, string $dayType, int $plannedMinutes, bool $isPast): ?array
    {
        $type = (string) ($override['type'] ?? '');
        $durationUnit = (string) ($override['duration_unit'] ?? 'day');
        $base = [
            'scheduled_minutes' => $plannedMinutes,
            'absence_code' => '',
            'leave_type_id' => $override['leave_type_id'] ?? null,
            'leave_type_name' => (string) ($override['leave_type_name'] ?? ''),
            'leave_type_code' => trim((string) ($override['leave_type_code'] ?? '')),
            'calendar_day_type' => $dayType,
            'duration_unit' => $durationUnit,
            'partial_day_part' => $override['partial_day_part'] ?? null,
            'starts_time' => $override['starts_time'] ?? null,
            'ends_time' => $override['ends_time'] ?? null,
            'total_minutes' => $override['total_minutes'] ?? null,
            'covered_leave_minutes' => 0,
        ];

        if ($type === 'leave') {
            $base['absence_code'] = strtoupper((string) ($override['absence_code'] ?? 'LEAVE'));

            if (in_array($durationUnit, ['half_day', 'hour'], true) && $dayType === 'workday') {
                if (! $isPast || $plannedMinutes <= 0) {
                    return $base + ['worked_minutes' => 0, 'attendance_status' => 'leave'];
                }

                $covered = $durationUnit === 'hour'
                    ? min($plannedMinutes, max(0, (int) ($override['total_minutes'] ?? 0)))
                    : intdiv($plannedMinutes, 2);

                $base['covered_leave_minutes'] = $covered;

                return $base + ['worked_minutes' => max(0, $plannedMinutes - $covered), 'attendance_status' => 'present'];
            }

            return $base + ['worked_minutes' => 0, 'attendance_status' => 'leave'];
        }

        if ($type === 'vacation') {
            return $base + ['worked_minutes' => 0, 'attendance_status' => 'vacation'];
        }

        if ($type === 'business_trip') {
            return $base + [
                'worked_minutes' => $isPast && $dayType === 'workday' ? $plannedMinutes : 0,
                'attendance_status' => 'business_trip',
            ];
        }

        return null;
    }

    /**
     * @param  array<int,string>  $tabelNos
     * @return array<string,array<int,AttendanceShiftAssignment>>
     */
    private function shiftAssignmentsByTabel(array $tabelNos, Carbon $from, Carbon $to): array
    {
        return AttendanceShiftAssignment::query()
            ->whereIn('tabel_no', $tabelNos)
            ->where('is_active', true)
            ->where('effective_from', '<=', $to->toDateString())
            ->where(function ($query) use ($from): void {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $from->toDateString());
            })
            ->orderByDesc('effective_from')
            ->get(['id', 'tabel_no', 'shift_id', 'effective_from', 'effective_to'])
            ->groupBy('tabel_no')
            ->map(fn (Collection $items) => $items->all())
            ->all();
    }

    /**
     * @param  array<int,AttendanceShiftAssignment>  $assignments  effective_from üzrə azalan sırada
     * @param  array<int,AttendanceShift>  $shiftsById
     */
    private function resolveShiftForDate(array $assignments, Carbon $date, array $shiftsById, ?AttendanceShift $defaultShift): ?AttendanceShift
    {
        foreach ($assignments as $assignment) {
            $effectiveFrom = $assignment->effective_from?->copy()->startOfDay();
            $effectiveTo = $assignment->effective_to?->copy()->endOfDay();

            if ($effectiveFrom === null || $date->lt($effectiveFrom) || ($effectiveTo !== null && $date->gt($effectiveTo))) {
                continue;
            }

            return $shiftsById[(int) $assignment->shift_id] ?? $defaultShift;
        }

        return $defaultShift;
    }
}
