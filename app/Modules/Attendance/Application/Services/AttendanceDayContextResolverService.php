<?php

namespace App\Modules\Attendance\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\AttendanceCalendar;
use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceDayContextResolverService
{
    /**
     * @param  Collection<int,string>  $tabelNos
     * @param  array<string,int|null>  $structureByTabel
     * @return array{
     *   calendars_global:array<string,string>,
     *   calendars_structure:array<string,string>,
     *   overrides:array<string,array{type:string,priority:int,source:string}>,
     *   override_keys_by_tabel:array<string,array<int,string>>
     * }
     */
    public function build(Carbon $from, Carbon $to, Collection $tabelNos, array $structureByTabel = []): array
    {
        $tabelNos = $tabelNos->filter()->values();
        $structureIds = collect($structureByTabel)->filter()->unique()->values();

        $calendars = AttendanceCalendar::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($query) use ($structureIds): void {
                $query->where('scope_type', 'global');

                if ($structureIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($structureIds): void {
                        $q->where('scope_type', 'structure')
                            ->whereIn('scope_id', $structureIds);
                    });
                }
            })
            ->get(['date', 'day_type', 'scope_type', 'scope_id']);

        $calendarGlobal = [];
        $calendarStructure = [];

        foreach ($calendars as $calendar) {
            $dateKey = $calendar->date?->toDateString();
            if (! $dateKey) {
                continue;
            }

            if ($calendar->scope_type === 'structure' && $calendar->scope_id) {
                $calendarStructure[$calendar->scope_id.'|'.$dateKey] = (string) $calendar->day_type;
                continue;
            }

            $calendarGlobal[$dateKey] = (string) $calendar->day_type;
        }

        $overrides = [];
        $keysByTabel = [];

        if ($tabelNos->isNotEmpty()) {
            $this->collectLeaveOverrides($tabelNos, $from, $to, $overrides, $keysByTabel);
            $this->collectVacationOverrides($tabelNos, $from, $to, $overrides, $keysByTabel);
            $this->collectBusinessTripOverrides($tabelNos, $from, $to, $overrides, $keysByTabel);
        }

        return [
            'calendars_global' => $calendarGlobal,
            'calendars_structure' => $calendarStructure,
            'overrides' => $overrides,
            'override_keys_by_tabel' => $keysByTabel,
        ];
    }

    /**
     * @param  array<string,string>  $globalMap
     * @param  array<string,string>  $structureMap
     */
    public function resolveCalendarDayType(
        Carbon $date,
        ?int $structureId,
        array $globalMap,
        array $structureMap
    ): string {
        $dateKey = $date->toDateString();

        if ($structureId) {
            $structureKey = $structureId.'|'.$dateKey;
            if (array_key_exists($structureKey, $structureMap)) {
                return (string) $structureMap[$structureKey];
            }
        }

        if (array_key_exists($dateKey, $globalMap)) {
            return (string) $globalMap[$dateKey];
        }

        return $date->isWeekend() ? 'weekend' : 'workday';
    }

    /**
     * @param  array<string,array{type:string,priority:int,source:string}>  $overrideMap
     * @return array{type:string,priority:int,source:string}|null
     */
    public function resolveOverride(Carbon $date, string $tabelNo, array $overrideMap): ?array
    {
        $key = $tabelNo.'|'.$date->toDateString();

        return $overrideMap[$key] ?? null;
    }

    /**
     * @param  array<string,array{type:string,priority:int,source:string}>  $overrides
     * @param  array<string,array<int,string>>  $keysByTabel
     */
    private function collectLeaveOverrides(
        Collection $tabelNos,
        Carbon $from,
        Carbon $to,
        array &$overrides,
        array &$keysByTabel
    ): void {
        $rows = Leave::query()
            ->whereIn('tabel_no', $tabelNos)
            ->whereDate('starts_at', '<=', $to->toDateString())
            ->whereDate('ends_at', '>=', $from->toDateString())
            ->where(function ($query): void {
                $query->where('status_id', OrderStatusEnum::APPROVED->value)
                    ->orWhereNotNull('approved_at');
            })
            ->get(['tabel_no', 'starts_at', 'ends_at']);

        foreach ($rows as $row) {
            $start = Carbon::parse($row->starts_at)->startOfDay();
            $end = Carbon::parse($row->ends_at)->endOfDay();

            $this->walkDateRange(
                tabelNo: (string) $row->tabel_no,
                from: $from,
                to: $to,
                start: $start,
                end: $end,
                type: 'leave',
                priority: 300,
                source: 'leave',
                overrides: $overrides,
                keysByTabel: $keysByTabel
            );
        }
    }

    /**
     * @param  array<string,array{type:string,priority:int,source:string}>  $overrides
     * @param  array<string,array<int,string>>  $keysByTabel
     */
    private function collectVacationOverrides(
        Collection $tabelNos,
        Carbon $from,
        Carbon $to,
        array &$overrides,
        array &$keysByTabel
    ): void {
        $rows = PersonnelVacation::query()
            ->whereIn('tabel_no', $tabelNos)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get(['tabel_no', 'start_date', 'end_date']);

        foreach ($rows as $row) {
            $start = Carbon::parse($row->start_date)->startOfDay();
            $end = Carbon::parse($row->end_date)->endOfDay();

            $this->walkDateRange(
                tabelNo: (string) $row->tabel_no,
                from: $from,
                to: $to,
                start: $start,
                end: $end,
                type: 'vacation',
                priority: 200,
                source: 'vacation',
                overrides: $overrides,
                keysByTabel: $keysByTabel
            );
        }
    }

    /**
     * @param  array<string,array{type:string,priority:int,source:string}>  $overrides
     * @param  array<string,array<int,string>>  $keysByTabel
     */
    private function collectBusinessTripOverrides(
        Collection $tabelNos,
        Carbon $from,
        Carbon $to,
        array &$overrides,
        array &$keysByTabel
    ): void {
        $rows = PersonnelBusinessTrip::query()
            ->whereIn('tabel_no', $tabelNos)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get(['tabel_no', 'start_date', 'end_date']);

        foreach ($rows as $row) {
            $start = Carbon::parse($row->start_date)->startOfDay();
            $end = Carbon::parse($row->end_date)->endOfDay();

            $this->walkDateRange(
                tabelNo: (string) $row->tabel_no,
                from: $from,
                to: $to,
                start: $start,
                end: $end,
                type: 'business_trip',
                priority: 100,
                source: 'business_trip',
                overrides: $overrides,
                keysByTabel: $keysByTabel
            );
        }
    }

    /**
     * @param  array<string,array{type:string,priority:int,source:string}>  $overrides
     * @param  array<string,array<int,string>>  $keysByTabel
     */
    private function walkDateRange(
        string $tabelNo,
        Carbon $from,
        Carbon $to,
        Carbon $start,
        Carbon $end,
        string $type,
        int $priority,
        string $source,
        array &$overrides,
        array &$keysByTabel
    ): void {
        $cursor = $start->copy()->greaterThan($from) ? $start->copy() : $from->copy();
        $rangeEnd = $end->copy()->lessThan($to) ? $end->copy() : $to->copy();

        while ($cursor->lte($rangeEnd)) {
            $dateKey = $cursor->toDateString();
            $key = $tabelNo.'|'.$dateKey;
            $current = $overrides[$key] ?? null;

            if ($current === null || $priority > ((int) ($current['priority'] ?? 0))) {
                $overrides[$key] = [
                    'type' => $type,
                    'priority' => $priority,
                    'source' => $source,
                ];
            }

            $keysByTabel[$tabelNo] ??= [];
            $keysByTabel[$tabelNo][] = $dateKey;
            $cursor->addDay();
        }
    }
}

