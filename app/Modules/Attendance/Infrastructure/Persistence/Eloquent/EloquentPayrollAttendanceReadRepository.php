<?php

namespace App\Modules\Attendance\Infrastructure\Persistence\Eloquent;

use App\Enums\OrderStatusEnum;
use App\Models\AttendanceMonthlySummary;
use App\Modules\Attendance\Domain\Contracts\PayrollAttendanceReadRepository;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EloquentPayrollAttendanceReadRepository implements PayrollAttendanceReadRepository
{
    /**
     * Leave-type attendance codes treated as unpaid for proration.
     * ponytail: free-text codes (no is_paid flag on leave_types); standardise to a flag if it matters.
     *
     * @var array<int,string>
     */
    private const UNPAID_LEAVE_CODES = ['UNPAID', 'ODENISSIZ', 'ÖDƏNİŞSİZ', 'UP'];

    public function monthlyAbsence(string $tabelNo, int $year, int $month): ?array
    {
        $row = AttendanceMonthlySummary::query()
            ->where('tabel_no', $tabelNo)
            ->where('year', $year)
            ->where('month', $month)
            ->first(['total_workdays', 'total_absence_days']);

        if (! $row) {
            return null;
        }

        return [
            'working_days' => (int) $row->total_workdays,
            // Unrecorded absences (absent/manual_absence) + approved unpaid-leave days in the month.
            'absence_days' => (int) $row->total_absence_days + $this->unpaidLeaveDays($tabelNo, $year, $month),
        ];
    }

    private function unpaidLeaveDays(string $tabelNo, int $year, int $month): int
    {
        if (! InstalledTables::has('leaves') || ! InstalledTables::has('leave_types')) {
            return 0;
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return (int) DB::table('leaves')
            ->join('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
            ->where('leaves.tabel_no', $tabelNo)
            ->where('leaves.status_id', OrderStatusEnum::APPROVED->value)
            ->whereNull('leaves.deleted_at')
            ->whereBetween('leaves.starts_at', [$start, $end])
            ->whereIn(DB::raw('UPPER(leave_types.attendance_code)'), self::UNPAID_LEAVE_CODES)
            ->sum('leaves.total_days');
    }
}
