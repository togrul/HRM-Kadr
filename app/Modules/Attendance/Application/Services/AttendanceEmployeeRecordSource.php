<?php

namespace App\Modules\Attendance\Application\Services;

use App\Contracts\EmployeeRecordSource;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\DB;

/**
 * Davamiyyətin töhfəsi: real faktlar (giriş-çıxış qeydləri, əl ilə daxiletmələr, iş vaxtından
 * artıq iş sorğuları, işlənmiş və ya təsdiqlənmiş/kilidlənmiş tabel günləri, kilidli aylar).
 * Sistemin avtomatik qurduğu boş tabel günləri fakt sayılmır.
 */
class AttendanceEmployeeRecordSource implements EmployeeRecordSource
{
    private const CLOSED_REQUEST_STATUSES = ['rejected', 'cancelled'];

    public function recordCounts(int $personnelId, string $tabelNo): array
    {
        $counts = [];

        if (InstalledTables::has('attendance_raw_punches')) {
            $counts['attendance_punches'] = DB::table('attendance_raw_punches')->where('tabel_no', $tabelNo)->count();
        }

        if (InstalledTables::has('attendance_manual_entries')) {
            $counts['attendance_manual_entries'] = DB::table('attendance_manual_entries')
                ->where('tabel_no', $tabelNo)
                ->whereNotIn('approval_status', self::CLOSED_REQUEST_STATUSES)
                ->count();
        }

        if (InstalledTables::has('attendance_overtime_requests')) {
            $counts['attendance_overtime_requests'] = DB::table('attendance_overtime_requests')
                ->where('tabel_no', $tabelNo)
                ->whereNotIn('status', self::CLOSED_REQUEST_STATUSES)
                ->count();
        }

        if (InstalledTables::has('attendance_daily_ledgers')) {
            $counts['timesheet_days'] = DB::table('attendance_daily_ledgers')
                ->where('tabel_no', $tabelNo)
                ->where(function ($query): void {
                    $query->where('worked_minutes', '>', 0)
                        ->orWhere('is_locked', true)
                        ->orWhereNotNull('approved_at')
                        ->orWhere('source_summary', '!=', 'system');
                })
                ->count();
        }

        if (InstalledTables::has('attendance_monthly_summaries')) {
            $counts['timesheet_locked_months'] = DB::table('attendance_monthly_summaries')
                ->where('tabel_no', $tabelNo)
                ->where('is_locked', true)
                ->count();
        }

        return array_filter($counts);
    }
}
