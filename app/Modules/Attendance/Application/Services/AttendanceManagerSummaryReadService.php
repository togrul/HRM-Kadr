<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class AttendanceManagerSummaryReadService
{
    public function paginateRows(
        int $year,
        int $month,
        string $search,
        int $perPage,
        array $structureIds = [],
        bool $onlyProblematic = false
    ): LengthAwarePaginator {
        return $this->baseQuery($year, $month, $search, $structureIds, $onlyProblematic)->paginate($perPage);
    }

    /**
     * @return array<string,int|float>
     */
    public function totals(int $year, int $month, array $structureIds = []): array
    {
        $aggregate = DB::query()
            ->fromSub(
                $this->baseQuery($year, $month, '', $structureIds, false)->toBase(),
                'manager_rows'
            )
            ->selectRaw('COUNT(*) as personnel_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN absence_days > 0 OR late_minutes > 0 OR early_leave_minutes > 0 OR open_exception_count > 0 THEN 1 ELSE 0 END), 0) as problem_personnel_count')
            ->selectRaw('COALESCE(SUM(absence_days), 0) as absence_days')
            ->selectRaw('COALESCE(SUM(late_minutes), 0) as late_minutes')
            ->selectRaw('COALESCE(SUM(early_leave_minutes), 0) as early_leave_minutes')
            ->selectRaw('COALESCE(SUM(overtime_minutes), 0) as overtime_minutes')
            ->selectRaw('COALESCE(SUM(open_exception_count), 0) as open_exception_count')
            ->first();

        return [
            'personnel_count' => (int) ($aggregate?->personnel_count ?? 0),
            'problem_personnel_count' => (int) ($aggregate?->problem_personnel_count ?? 0),
            'absence_days' => (int) ($aggregate?->absence_days ?? 0),
            'late_minutes' => (int) ($aggregate?->late_minutes ?? 0),
            'early_leave_minutes' => (int) ($aggregate?->early_leave_minutes ?? 0),
            'overtime_minutes' => (int) ($aggregate?->overtime_minutes ?? 0),
            'open_exception_count' => (int) ($aggregate?->open_exception_count ?? 0),
        ];
    }

    private function baseQuery(
        int $year,
        int $month,
        string $search,
        array $structureIds = [],
        bool $onlyProblematic = false
    ): Builder {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $exceptionSubquery = DB::table('attendance_exceptions')
            ->selectRaw('tabel_no, COUNT(*) as open_exception_count')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'open')
            ->groupBy('tabel_no');

        $query = Personnel::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->from('personnels as p')
            ->leftJoin('attendance_daily_ledgers as l', function ($join) use ($start, $end): void {
                $join->on('p.tabel_no', '=', 'l.tabel_no')
                    ->whereBetween('l.date', [$start->toDateString(), $end->toDateString()]);
            })
            ->leftJoinSub($exceptionSubquery, 'ex', function ($join): void {
                $join->on('p.tabel_no', '=', 'ex.tabel_no');
            })
            ->where('p.is_pending', 0)
            ->whereNull('p.deleted_at')
            ->whereDate('p.join_work_date', '<=', $end->toDateString())
            ->where(function (Builder $query) use ($start): void {
                $query->whereNull('p.leave_work_date')
                    ->orWhereDate('p.leave_work_date', '>=', $start->toDateString());
            })
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('p.structure_id', $structureIds))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $wildcard = '%'.$search.'%';
                $query->where(function (Builder $inner) use ($wildcard): void {
                    $inner->where('p.tabel_no', 'like', $wildcard)
                        ->orWhere('p.name', 'like', $wildcard)
                        ->orWhere('p.surname', 'like', $wildcard)
                        ->orWhere('p.patronymic', 'like', $wildcard);
                });
            })
            ->selectRaw('
                p.id,
                p.tabel_no,
                p.surname,
                p.name,
                p.patronymic,
                p.structure_id,
                COALESCE(SUM(l.scheduled_minutes), 0) as scheduled_minutes,
                COALESCE(SUM(l.worked_minutes), 0) as worked_minutes,
                COALESCE(SUM(l.break_minutes), 0) as break_minutes,
                COALESCE(SUM(l.late_minutes), 0) as late_minutes,
                COALESCE(SUM(CASE WHEN l.late_minutes > 0 THEN 1 ELSE 0 END), 0) as late_days,
                COALESCE(SUM(l.early_leave_minutes), 0) as early_leave_minutes,
                COALESCE(SUM(CASE WHEN l.early_leave_minutes > 0 THEN 1 ELSE 0 END), 0) as early_leave_days,
                COALESCE(SUM(l.overtime_minutes), 0) as overtime_minutes,
                COALESCE(SUM(CASE WHEN l.scheduled_minutes > 0 THEN 1 ELSE 0 END), 0) as scheduled_days,
                COALESCE(SUM(CASE WHEN l.attendance_status IN ("present","manual_present","holiday_worked","weekend_worked") OR l.worked_minutes > 0 THEN 1 ELSE 0 END), 0) as present_days,
                COALESCE(SUM(CASE WHEN l.attendance_status IN ("absent","manual_absence") THEN 1 ELSE 0 END), 0) as absence_days,
                COALESCE(MAX(ex.open_exception_count), 0) as open_exception_count
            ')
            ->groupBy('p.id', 'p.tabel_no', 'p.surname', 'p.name', 'p.patronymic', 'p.structure_id')
            ->orderByDesc('absence_days')
            ->orderByDesc('late_minutes')
            ->orderBy('p.surname')
            ->orderBy('p.name');

        if ($onlyProblematic) {
            $query->havingRaw(
                'SUM(CASE WHEN l.attendance_status IN ("absent","manual_absence") THEN 1 ELSE 0 END) > 0
                OR SUM(l.late_minutes) > 0
                OR SUM(l.early_leave_minutes) > 0
                OR COALESCE(MAX(ex.open_exception_count), 0) > 0'
            );
        }

        return $query;
    }
}
