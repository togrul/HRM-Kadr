<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\AttendanceCalendar;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Carbon;

/**
 * Working-day arithmetic for KPI deadlines, on the organisation-wide attendance
 * calendar: a day marked holiday or weekend is skipped, a day marked workday counts
 * even on a Saturday (a moved working day), and unmarked days fall back to Mon–Fri.
 */
class WorkingDays
{
    /** @var array<int, array<string, string>> year → date → day type */
    private array $years = [];

    public function add(Carbon $date, int $days): Carbon
    {
        return $this->step($date, $days, 1);
    }

    public function sub(Carbon $date, int $days): Carbon
    {
        return $this->step($date, $days, -1);
    }

    public function isWorkingDay(Carbon $date): bool
    {
        return match ($this->dayType($date)) {
            'workday' => true,
            'holiday', 'weekend' => false,
            default => ! $date->isWeekend(),
        };
    }

    private function step(Carbon $date, int $days, int $direction): Carbon
    {
        $day = $date->copy()->startOfDay();
        for ($left = $days; $left > 0;) {
            $day->addDays($direction);
            if ($this->isWorkingDay($day)) {
                $left--;
            }
        }

        return $day;
    }

    private function dayType(Carbon $date): ?string
    {
        $year = $date->year;

        if (! isset($this->years[$year])) {
            $this->years[$year] = InstalledTables::has('attendance_calendars')
                ? AttendanceCalendar::query()
                    ->where('scope_type', 'global')
                    ->whereYear('date', $year)
                    ->get(['date', 'day_type'])
                    ->mapWithKeys(fn (AttendanceCalendar $day): array => [$day->date->toDateString() => $day->day_type])
                    ->all()
                : [];
        }

        return $this->years[$year][$date->toDateString()] ?? null;
    }
}
