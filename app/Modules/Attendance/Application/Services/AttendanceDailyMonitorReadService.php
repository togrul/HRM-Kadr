<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\Personnel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceDailyMonitorReadService
{
    public function paginateRows(
        string $date,
        string $search,
        string $statusFilter,
        int $perPage
    ): LengthAwarePaginator {
        return $this->baseQuery($date, $search, $statusFilter)->paginate($perPage);
    }

    /**
     * @return array{present:int,late:int,absent:int,missing:int}
     */
    public function totals(
        string $date,
        string $search,
        string $statusFilter
    ): array {
        return [
            'present' => (clone $this->baseQuery($date, $search, $statusFilter))->where(function (Builder $query): void {
                $query->where('l.worked_minutes', '>', 0)
                    ->orWhereIn('l.attendance_status', ['present', 'manual_present', 'holiday_worked', 'weekend_worked']);
            })->count(),
            'late' => (clone $this->baseQuery($date, $search, $statusFilter))->where('l.late_minutes', '>', 0)->count(),
            'absent' => (clone $this->baseQuery($date, $search, $statusFilter))->whereIn('l.attendance_status', ['absent', 'manual_absence'])->count(),
            'missing' => (clone $this->baseQuery($date, $search, $statusFilter))->whereNull('l.id')->count(),
        ];
    }

    private function baseQuery(string $date, string $search, string $statusFilter): Builder
    {
        return Personnel::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->from('personnels as p')
            ->leftJoin('attendance_daily_ledgers as l', function ($join) use ($date): void {
                $join->on('p.tabel_no', '=', 'l.tabel_no')
                    ->whereDate('l.date', $date);
            })
            ->where('p.is_pending', 0)
            ->whereNull('p.leave_work_date')
            ->whereNull('p.deleted_at')
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
                'p.tabel_no',
                'p.surname',
                'p.name',
                'p.patronymic',
                'l.id as ledger_id',
                'l.worked_minutes',
                'l.late_minutes',
                'l.early_leave_minutes',
                'l.attendance_status',
            ]);
    }
}
