<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceMonthlySummary;
use App\Modules\Attendance\Contracts\AttendanceMonthLock;
use App\Support\Database\InstalledTables;

/**
 * Answers AttendanceMonthLock straight from the monthly summaries.
 *
 * Deliberately not AttendanceMonthLockService::isPeriodLocked(): that one memoizes per
 * process for hot read paths, while this answer guards a rare, consequential write and
 * must reflect a lock taken a moment ago. Without the attendance tables (module off)
 * nothing is locked.
 */
class AttendanceMonthLockReader implements AttendanceMonthLock
{
    public function isMonthLocked(int $year, int $month): bool
    {
        if (! InstalledTables::has('attendance_monthly_summaries')) {
            return false;
        }

        return AttendanceMonthlySummary::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('is_locked', true)
            ->exists();
    }
}
