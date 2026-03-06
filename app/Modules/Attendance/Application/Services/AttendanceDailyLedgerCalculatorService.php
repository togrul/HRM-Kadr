<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceManualEntry;
use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use Carbon\Carbon;

class AttendanceDailyLedgerCalculatorService
{
    /**
     * @param  array{
     *   worked_minutes:int,
     *   break_minutes:int,
     *   unmatched:int,
     *   first_in_at:?string,
     *   last_out_at:?string,
     *   consumed_punch_ids?:array<int,int>,
     *   pairs:array<int,array{in:string,out:string,duration_minutes:int}>
     * }  $pairing
     * @param  array{type:string,priority?:int,source?:string}|null  $override
     * @return array<string,mixed>
     */
    public function calculate(
        Carbon $date,
        array $pairing,
        ?AttendanceShift $shift = null,
        ?AttendanceManualEntry $manualEntry = null,
        ?AttendanceSetting $setting = null,
        string $calendarDayType = 'workday',
        ?array $override = null,
        ?int $approvedOvertimeMinutes = null
    ): array {
        $policy = app(AttendanceRulePolicyService::class);

        $scheduledMinutes = $this->resolveScheduledMinutes($date, $shift, $calendarDayType);

        if ($manualEntry !== null) {
            $workedMinutes = (int) $manualEntry->worked_minutes;
            $overtimeMinutes = max(0, (int) $manualEntry->overtime_minutes);
            $lateMinutes = max(0, (int) $manualEntry->late_minutes);
            $earlyLeaveMinutes = max(0, (int) $manualEntry->early_leave_minutes);
            $absenceCode = $manualEntry->absence_code ?: null;
            $status = $absenceCode !== null
                ? 'manual_absence'
                : ($workedMinutes > 0 ? 'manual_present' : 'manual_empty');

            return [
                'date' => $date->toDateString(),
                'shift_id' => $shift?->id,
                'scheduled_minutes' => $scheduledMinutes,
                'worked_minutes' => $workedMinutes,
                'break_minutes' => 0,
                'overtime_minutes' => $overtimeMinutes,
                'late_minutes' => $lateMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'attendance_status' => $status,
                'absence_code' => $absenceCode,
                'source_summary' => 'manual_override',
                'meta' => [
                    'manual_entry_id' => $manualEntry->id,
                    'approval_status' => $manualEntry->approval_status,
                    'late_minutes' => $lateMinutes,
                    'early_leave_minutes' => $earlyLeaveMinutes,
                ],
            ];
        }

        if ($override !== null) {
            return $this->buildOverridePayload(
                date: $date,
                shift: $shift,
                calendarDayType: $calendarDayType,
                override: $override,
                scheduledMinutes: $scheduledMinutes
            );
        }

        $workedMinutes = $policy->roundMinutes(max(0, (int) ($pairing['worked_minutes'] ?? 0)), $setting);
        $breakMinutes = $policy->roundMinutes(max(0, (int) ($pairing['break_minutes'] ?? 0)), $setting);
        $lateMinutes = 0;
        $earlyLeaveMinutes = 0;

        if ($shift !== null) {
            $graceLate = max(0, (int) ($setting?->late_grace_minutes ?? 0));
            $graceEarly = max(0, (int) ($setting?->early_leave_grace_minutes ?? 0));

            $window = app(AttendanceShiftWindowService::class)->resolve($date, $shift);
            $shiftStart = $window['shift_start'];
            $shiftEnd = $window['shift_end'];

            if (! empty($pairing['first_in_at'])) {
                $firstIn = Carbon::parse((string) $pairing['first_in_at']);
                $lateMinutes = $policy->applyGrace(
                    max(0, $shiftStart->diffInMinutes($firstIn, false)),
                    $graceLate
                );
            }

            if (! empty($pairing['last_out_at'])) {
                $lastOut = Carbon::parse((string) $pairing['last_out_at']);
                $earlyLeaveMinutes = $policy->applyGrace(
                    max(0, $lastOut->diffInMinutes($shiftEnd, false)),
                    $graceEarly
                );
            }
        }

        $overtimeMinutes = $policy->resolveOvertimeMinutes(
            workedMinutes: $workedMinutes,
            scheduledMinutes: $scheduledMinutes,
            calendarDayType: $calendarDayType,
            setting: $setting,
            approvedOvertimeMinutes: $approvedOvertimeMinutes
        );

        $status = $this->resolveStatus($calendarDayType, $workedMinutes);

        return [
            'date' => $date->toDateString(),
            'shift_id' => $shift?->id,
            'scheduled_minutes' => $scheduledMinutes,
            'worked_minutes' => $workedMinutes,
            'break_minutes' => $breakMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'late_minutes' => (int) $lateMinutes,
            'early_leave_minutes' => (int) $earlyLeaveMinutes,
            'attendance_status' => $status,
            'absence_code' => $status === 'absent' ? 'absent' : null,
            'source_summary' => 'system',
            'meta' => [
                'calendar_day_type' => $calendarDayType,
                'unmatched_punches' => (int) ($pairing['unmatched'] ?? 0),
                'pair_count' => count($pairing['pairs'] ?? []),
                'approved_overtime_minutes' => max(0, (int) ($approvedOvertimeMinutes ?? 0)),
                'overtime_policy' => (string) ($setting?->overtime_policy ?? config('attendance.processing.policy_defaults.overtime_policy', 'by_approval')),
            ],
        ];
    }

    private function resolveScheduledMinutes(Carbon $date, ?AttendanceShift $shift, string $calendarDayType): int
    {
        if ($shift === null || $calendarDayType !== 'workday') {
            return 0;
        }

        $window = app(AttendanceShiftWindowService::class)->resolve($date, $shift);
        $start = $window['shift_start'];
        $end = $window['shift_end'];

        return max(0, $start->diffInMinutes($end) - (int) $shift->break_minutes);
    }

    /**
     * @param  array{type:string,priority?:int,source?:string}  $override
     * @return array<string,mixed>
     */
    private function buildOverridePayload(
        Carbon $date,
        ?AttendanceShift $shift,
        string $calendarDayType,
        array $override,
        int $scheduledMinutes
    ): array {
        $type = (string) ($override['type'] ?? '');
        $status = match ($type) {
            'leave' => 'leave',
            'vacation' => 'vacation',
            'business_trip' => 'business_trip',
            default => 'override',
        };

        $workedMinutes = ($type === 'business_trip' && $calendarDayType === 'workday')
            ? $scheduledMinutes
            : 0;

        $absenceCode = match ($type) {
            'leave' => 'leave',
            'vacation' => 'vacation',
            default => null,
        };

        return [
            'date' => $date->toDateString(),
            'shift_id' => $shift?->id,
            'scheduled_minutes' => $scheduledMinutes,
            'worked_minutes' => $workedMinutes,
            'break_minutes' => 0,
            'overtime_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'attendance_status' => $status,
            'absence_code' => $absenceCode,
            'source_summary' => 'policy_override',
            'meta' => [
                'calendar_day_type' => $calendarDayType,
                'override_type' => $type,
                'override_source' => (string) ($override['source'] ?? $type),
            ],
        ];
    }

    private function resolveStatus(string $calendarDayType, int $workedMinutes): string
    {
        if ($calendarDayType === 'holiday') {
            return $workedMinutes > 0 ? 'holiday_worked' : 'holiday';
        }

        if ($calendarDayType === 'weekend') {
            return $workedMinutes > 0 ? 'weekend_worked' : 'weekend';
        }

        return $workedMinutes > 0 ? 'present' : 'absent';
    }
}
