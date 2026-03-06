<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceSetting;

class AttendanceRulePolicyService
{
    public function roundMinutes(int $minutes, ?AttendanceSetting $setting): int
    {
        $policy = (string) ($setting?->rounding_policy ?? config('attendance.processing.policy_defaults.rounding_policy', 'none'));
        $step = max(1, (int) ($setting?->rounding_step_minutes ?? config('attendance.processing.policy_defaults.rounding_step_minutes', 5)));
        $value = max(0, $minutes);

        if ($policy === 'none') {
            return $value;
        }

        if ($policy === 'floor') {
            return (int) (floor($value / $step) * $step);
        }

        if ($policy === 'ceil') {
            return (int) (ceil($value / $step) * $step);
        }

        // nearest
        return (int) (round($value / $step) * $step);
    }

    public function resolveOvertimeMinutes(
        int $workedMinutes,
        int $scheduledMinutes,
        string $calendarDayType,
        ?AttendanceSetting $setting,
        ?int $approvedOvertimeMinutes = null
    ): int {
        $policy = (string) ($setting?->overtime_policy ?? config('attendance.processing.policy_defaults.overtime_policy', 'by_approval'));
        $approved = max(0, (int) ($approvedOvertimeMinutes ?? 0));

        $computed = match ($policy) {
            'none' => 0,
            'all_worked' => max(0, $workedMinutes),
            'after_shift' => $calendarDayType === 'workday'
                ? max(0, $workedMinutes - $scheduledMinutes)
                : max(0, $workedMinutes),
            'by_approval' => $approved,
            default => $calendarDayType === 'workday'
                ? max(0, $workedMinutes - $scheduledMinutes)
                : max(0, $workedMinutes),
        };

        return $this->roundMinutes($computed, $setting);
    }

    public function applyGrace(int $deltaMinutes, int $graceMinutes): int
    {
        return max(0, $deltaMinutes - max(0, $graceMinutes));
    }
}

