<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceShift;
use Carbon\Carbon;

class AttendanceShiftWindowService
{
    /**
     * @return array{
     *   shift_start:Carbon,
     *   shift_end:Carbon,
     *   window_start:Carbon,
     *   window_end:Carbon
     * }
     */
    public function resolve(Carbon $date, ?AttendanceShift $shift): array
    {
        $baseDate = $date->copy()->startOfDay();

        if ($shift === null) {
            return [
                'shift_start' => $baseDate->copy()->startOfDay(),
                'shift_end' => $baseDate->copy()->endOfDay(),
                'window_start' => $baseDate->copy()->startOfDay(),
                'window_end' => $baseDate->copy()->endOfDay(),
            ];
        }

        $shiftStart = Carbon::parse($baseDate->toDateString().' '.$shift->start_time);
        $shiftEnd = Carbon::parse($baseDate->toDateString().' '.$shift->end_time);

        if ((bool) $shift->is_night_shift || $shiftEnd->lte($shiftStart)) {
            $shiftEnd->addDay();
        }

        $windowStart = $shiftStart->copy()->subMinutes(max(0, (int) $shift->in_flex_before_minutes));
        $windowEnd = $shiftEnd->copy()->addMinutes(max(0, (int) $shift->out_flex_after_minutes));

        // Ensure we do not cut valid out punch records for night/cross-day shifts.
        if ($windowEnd->lte($windowStart)) {
            $windowEnd = $windowStart->copy()->addDay();
        }

        return [
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
        ];
    }
}

