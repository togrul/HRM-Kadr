<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceCalendar;
use Carbon\Carbon;

class AttendanceWeekendCalendarSeederService
{
    /**
     * @return array{created:int,skipped_existing:int,weekend_days:int}
     */
    public function seedMonth(int $year, int $month, ?int $causerId = null): array
    {
        $cursor = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $cursor->copy()->endOfMonth();

        $created = 0;
        $skippedExisting = 0;
        $weekendDays = 0;

        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend()) {
                $cursor->addDay();
                continue;
            }

            $weekendDays++;
            $date = $cursor->toDateString();

            $existing = AttendanceCalendar::query()
                ->where('scope_type', 'global')
                ->whereNull('scope_id')
                ->whereDate('date', $date)
                ->first();

            if ($existing instanceof AttendanceCalendar) {
                $skippedExisting++;
                $cursor->addDay();
                continue;
            }

            $calendar = AttendanceCalendar::query()->create([
                'date' => $date,
                'day_type' => 'weekend',
                'name' => 'attendance::calendar_regimes.auto_labels.weekend',
                'is_paid' => false,
                'scope_type' => 'global',
                'scope_id' => null,
                'created_by' => $causerId,
                'updated_by' => $causerId,
            ]);

            app(AttendanceAuditLogger::class)->log(
                event: 'calendar.weekend_seeded',
                description: 'Attendance weekend calendar auto-created.',
                subject: $calendar,
                properties: [
                    'date' => $date,
                    'day_type' => 'weekend',
                    'scope_type' => 'global',
                    'scope_id' => null,
                    'auto_seeded' => true,
                ],
                causerId: $causerId
            );

            $created++;
            $cursor->addDay();
        }

        return [
            'created' => $created,
            'skipped_existing' => $skippedExisting,
            'weekend_days' => $weekendDays,
        ];
    }
}
