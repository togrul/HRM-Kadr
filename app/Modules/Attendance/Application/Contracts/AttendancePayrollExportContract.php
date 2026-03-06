<?php

namespace App\Modules\Attendance\Application\Contracts;

use App\Models\AttendanceMonthlySummary;

class AttendancePayrollExportContract
{
    /**
     * @return array<int,string>
     */
    public function headers(): array
    {
        return [
            '#',
            __('Tabel no'),
            __('Fullname'),
            __('Scheduled (hours)'),
            __('Worked (hours)'),
            __('Overtime (hours)'),
            __('Absent (hours)'),
            __('Workdays'),
            __('Present days'),
            __('Absent days'),
        ];
    }

    /**
     * @return array<int,int|float|string>
     */
    public function mapRow(AttendanceMonthlySummary $row, int $index): array
    {
        $personnel = $row->personnel;
        $fullname = trim(($personnel?->surname ?? '').' '.($personnel?->name ?? '').' '.($personnel?->patronymic ?? ''));

        return [
            $index,
            $row->tabel_no,
            $fullname,
            round(((int) $row->total_scheduled_minutes) / 60, 2),
            round(((int) $row->total_worked_minutes) / 60, 2),
            round(((int) $row->total_overtime_minutes) / 60, 2),
            round(((int) $row->total_absence_minutes) / 60, 2),
            (int) $row->total_workdays,
            (int) $row->total_present_days,
            (int) $row->total_absence_days,
        ];
    }
}
