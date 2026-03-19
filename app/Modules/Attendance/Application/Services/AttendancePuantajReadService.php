<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceCalendar;
use App\Models\AttendanceDailyLedger;
use App\Models\Personnel;
use App\Models\Structure;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendancePuantajReadService
{
    public function paginatePersonnels(
        string $search,
        int $perPage,
        array $structureIds = [],
        ?Carbon $from = null,
        ?Carbon $to = null
    ): LengthAwarePaginator
    {
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
            ->get(['tabel_no', 'date', 'worked_minutes', 'attendance_status', 'absence_code', 'meta']);

        return $ledgers
            ->groupBy('tabel_no')
            ->map(function (Collection $items): array {
                return $items->mapWithKeys(fn (AttendanceDailyLedger $ledger) => [
                    $ledger->date->toDateString() => [
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
     * @return array<string,string>
     */
    public function globalCalendarDayTypeByDate(Carbon $from, Carbon $to): array
    {
        return AttendanceCalendar::query()
            ->where('scope_type', 'global')
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['date', 'day_type'])
            ->mapWithKeys(fn (AttendanceCalendar $calendar) => [
                $calendar->date->toDateString() => (string) $calendar->day_type,
            ])
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
}
