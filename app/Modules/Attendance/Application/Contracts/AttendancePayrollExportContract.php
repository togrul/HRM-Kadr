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
            __('attendance::month_close.payroll_headers.tabel_no'),
            __('attendance::month_close.payroll_headers.fullname'),
            __('attendance::month_close.payroll_headers.scheduled_hours'),
            __('attendance::month_close.payroll_headers.worked_hours'),
            __('attendance::month_close.payroll_headers.overtime_hours'),
            __('attendance::month_close.payroll_headers.absent_hours'),
            __('attendance::month_close.payroll_headers.workdays'),
            __('attendance::month_close.payroll_headers.present_days'),
            __('attendance::month_close.payroll_headers.absent_days'),
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
