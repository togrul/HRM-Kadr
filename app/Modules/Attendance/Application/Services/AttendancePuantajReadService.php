<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceCalendar;
use App\Models\AttendanceDailyLedger;
use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendancePuantajReadService
{
    public function paginatePersonnels(string $search, int $perPage, array $structureIds = []): LengthAwarePaginator
    {
        return Personnel::query()
            ->where('is_pending', 0)
            ->whereNull('leave_work_date')
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
            ->get(['tabel_no', 'date', 'worked_minutes', 'attendance_status', 'absence_code']);

        return $ledgers
            ->groupBy('tabel_no')
            ->map(function (Collection $items): array {
                return $items->mapWithKeys(fn (AttendanceDailyLedger $ledger) => [
                    $ledger->date->toDateString() => [
                        'worked_minutes' => (int) $ledger->worked_minutes,
                        'attendance_status' => (string) $ledger->attendance_status,
                        'absence_code' => (string) ($ledger->absence_code ?? ''),
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
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['date', 'day_type'])
            ->mapWithKeys(fn (AttendanceCalendar $calendar) => [
                $calendar->date->toDateString() => (string) $calendar->day_type,
            ])
            ->all();
    }
}
