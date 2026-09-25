<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\Personnel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class AttendanceDailyMonitorReadService
{
    public function paginateRows(
        string $date,
        string $search,
        string $statusFilter,
        int $perPage,
        array $structureIds = []
    ): LengthAwarePaginator {
        return $this->baseQuery($date, $search, $statusFilter, $structureIds)->paginate($perPage);
    }

    /**
     * @return array{present:int,late:int,absent:int,missing:int}
     */
    public function totals(
        string $date,
        string $search,
        string $statusFilter,
        array $structureIds = []
    ): array {
        // One pass with conditional sums instead of four counts over the same join.
        $row = $this->baseQuery($date, $search, $statusFilter, $structureIds)
            ->reorder()
            ->toBase()
            ->selectRaw("sum(case when l.worked_minutes > 0 or l.attendance_status in ('present', 'manual_present', 'holiday_worked', 'weekend_worked') then 1 else 0 end) as present_count")
            ->selectRaw('sum(case when l.late_minutes > 0 then 1 else 0 end) as late_count')
            ->selectRaw("sum(case when l.attendance_status in ('absent', 'manual_absence') then 1 else 0 end) as absent_count")
            ->selectRaw('sum(case when l.id is null then 1 else 0 end) as missing_count')
            ->first();

        return [
            'present' => (int) ($row->present_count ?? 0),
            'late' => (int) ($row->late_count ?? 0),
            'absent' => (int) ($row->absent_count ?? 0),
            'missing' => (int) ($row->missing_count ?? 0),
        ];
    }

    private function baseQuery(string $date, string $search, string $statusFilter, array $structureIds = []): Builder
    {
        // Half-open day ranges, not whereDate(): DATE(col) cannot use the (tabel_no, date) index.
        $day = Carbon::parse($date)->toDateString();
        $nextDay = Carbon::parse($date)->addDay()->toDateString();

        return Personnel::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->from('personnels as p')
            ->leftJoin('attendance_daily_ledgers as l', function ($join) use ($day, $nextDay): void {
                $join->on('p.tabel_no', '=', 'l.tabel_no')
                    ->where('l.date', '>=', $day)
                    ->where('l.date', '<', $nextDay);
            })
            ->where('p.is_pending', 0)
            ->whereNull('p.deleted_at')
            ->where('p.join_work_date', '<', $nextDay)
            ->where(function (Builder $query) use ($day): void {
                $query->whereNull('p.leave_work_date')
                    ->orWhere('p.leave_work_date', '>=', $day);
            })
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('p.structure_id', $structureIds))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $wildcard = '%'.$search.'%';
                $query->where(function (Builder $q) use ($wildcard): void {
                    $q->where('p.tabel_no', 'like', $wildcard)
                        ->orWhere('p.name', 'like', $wildcard)
                        ->orWhere('p.surname', 'like', $wildcard)
                        ->orWhere('p.patronymic', 'like', $wildcard);
                });
            })
            ->when($statusFilter !== 'all', function (Builder $query) use ($statusFilter): void {
                match ($statusFilter) {
                    'present' => $query->where(function (Builder $q): void {
                        $q->where('l.worked_minutes', '>', 0)
                            ->orWhereIn('l.attendance_status', ['present', 'manual_present', 'holiday_worked', 'weekend_worked']);
                    }),
                    'late' => $query->where('l.late_minutes', '>', 0),
                    'absent' => $query->whereIn('l.attendance_status', ['absent', 'manual_absence']),
                    'missing' => $query->whereNull('l.id'),
                    default => null,
                };
            })
            ->orderBy('p.surname')
            ->orderBy('p.name')
            ->select([
                // the list links each row to the personnel file, like the mockup's tabel column
                'p.id as personnel_id',
                'p.tabel_no',
                'p.surname',
                'p.name',
                'p.patronymic',
                'p.structure_id',
                'l.id as ledger_id',
                'l.worked_minutes',
                'l.late_minutes',
                'l.early_leave_minutes',
                'l.attendance_status',
            ]);
    }
}
