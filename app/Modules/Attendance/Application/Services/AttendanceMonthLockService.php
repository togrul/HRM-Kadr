<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceMonthlySummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceMonthLockService
{
    /**
     * @var array<string,bool>
     */
    protected static array $periodLockMemo = [];

    public function isPeriodLocked(Carbon|string $date): bool
    {
        $dt = $date instanceof Carbon ? $date : Carbon::parse($date);
        $key = $this->periodKey((int) $dt->year, (int) $dt->month);

        if (array_key_exists($key, self::$periodLockMemo)) {
            return self::$periodLockMemo[$key];
        }

        return self::$periodLockMemo[$key] = AttendanceMonthlySummary::query()
            ->where('year', (int) $dt->year)
            ->where('month', (int) $dt->month)
            ->where('is_locked', true)
            ->exists();
    }

    /**
     * @return array<string,int>
     */
    public function closeMonth(int $year, int $month): array
    {
        $stats = $this->snapshotMonth($year, $month, true);
        $this->forgetPeriodLock($year, $month);

        app(AttendanceAuditLogger::class)->log(
            event: 'month_lock.closed',
            description: 'Attendance month closed and locked.',
            properties: [
                'year' => $year,
                'month' => $month,
                'stats' => $stats,
            ]
        );

        return $stats;
    }

    /**
     * @return array<string,int>
     */
    public function snapshotMonth(int $year, int $month, bool $lock = false): array
    {
        [$from, $to] = $this->periodBounds($year, $month);

        $summaryRefreshStats = app(AttendanceStructureSummaryService::class)->rebuildRange($from, $to);

        $grouped = AttendanceDailyLedger::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('tabel_no')
            ->selectRaw('COALESCE(SUM(scheduled_minutes),0) as total_scheduled_minutes')
            ->selectRaw('COALESCE(SUM(worked_minutes),0) as total_worked_minutes')
            ->selectRaw('COALESCE(SUM(overtime_minutes),0) as total_overtime_minutes')
            ->selectRaw('COALESCE(SUM(CASE WHEN attendance_status IN ("absent","manual_absence") THEN scheduled_minutes ELSE 0 END),0) as total_absence_minutes')
            ->selectRaw('COALESCE(SUM(CASE WHEN attendance_status NOT IN ("holiday","weekend") THEN 1 ELSE 0 END),0) as total_workdays')
            ->selectRaw('COALESCE(SUM(CASE WHEN worked_minutes > 0 THEN 1 ELSE 0 END),0) as total_present_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN attendance_status IN ("absent","manual_absence") THEN 1 ELSE 0 END),0) as total_absence_days')
            ->groupBy('tabel_no')
            ->get();

        $summaryUpserts = 0;
        $lockedLedgers = 0;

        DB::transaction(function () use ($grouped, $year, $month, $from, $to, $lock, &$summaryUpserts, &$lockedLedgers): void {
            foreach ($grouped as $row) {
                AttendanceMonthlySummary::query()->updateOrCreate(
                    [
                        'tabel_no' => (string) $row->tabel_no,
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'total_scheduled_minutes' => (int) $row->total_scheduled_minutes,
                        'total_worked_minutes' => (int) $row->total_worked_minutes,
                        'total_overtime_minutes' => (int) $row->total_overtime_minutes,
                        'total_absence_minutes' => (int) $row->total_absence_minutes,
                        'total_workdays' => (int) $row->total_workdays,
                        'total_present_days' => (int) $row->total_present_days,
                        'total_absence_days' => (int) $row->total_absence_days,
                        'is_locked' => $lock,
                        'calculated_at' => now(),
                    ]
                );

                $summaryUpserts++;
            }

            if ($lock) {
                $lockedLedgers = AttendanceDailyLedger::query()
                    ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->update(['is_locked' => true]);
            }
        });

        $stats = [
            'summary_upserts' => $summaryUpserts,
            'locked_ledgers' => $lockedLedgers,
            'daily_structure_summary_upserts' => (int) ($summaryRefreshStats['upserts'] ?? 0),
        ];

        if ($lock) {
            $this->forgetPeriodLock($year, $month);
        }

        app(AttendanceCacheService::class)->forgetOverviewMonth($year, $month);

        app(AttendanceAuditLogger::class)->log(
            event: $lock ? 'month_lock.snapshot_locked' : 'month_lock.snapshot',
            description: $lock
                ? 'Attendance monthly snapshot generated with lock mode.'
                : 'Attendance monthly snapshot generated.',
            properties: [
                'year' => $year,
                'month' => $month,
                'lock_mode' => $lock,
                'stats' => $stats,
            ]
        );

        return $stats;
    }

    /**
     * @return array<string,int>
     */
    public function unlockMonth(int $year, int $month): array
    {
        [$from, $to] = $this->periodBounds($year, $month);

        $unlockedSummaries = AttendanceMonthlySummary::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('is_locked', true)
            ->update(['is_locked' => false]);

        $unlockedLedgers = AttendanceDailyLedger::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->where('is_locked', true)
            ->update(['is_locked' => false]);

        $stats = [
            'unlocked_summaries' => $unlockedSummaries,
            'unlocked_ledgers' => $unlockedLedgers,
        ];

        $this->forgetPeriodLock($year, $month);

        app(AttendanceCacheService::class)->forgetOverviewMonth($year, $month);

        app(AttendanceAuditLogger::class)->log(
            event: 'month_lock.unlocked',
            description: 'Attendance month unlocked.',
            properties: [
                'year' => $year,
                'month' => $month,
                'stats' => $stats,
            ]
        );

        return $stats;
    }

    /**
     * @return array<string,mixed>
     */
    public function periodStatus(int $year, int $month): array
    {
        [$from, $to] = $this->periodBounds($year, $month);

        $totalLedgers = AttendanceDailyLedger::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->count();

        $lockedLedgers = AttendanceDailyLedger::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->where('is_locked', true)
            ->count();

        $summaryRows = AttendanceMonthlySummary::query()
            ->where('year', $year)
            ->where('month', $month)
            ->count();

        $lockedSummaryRows = AttendanceMonthlySummary::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('is_locked', true)
            ->count();

        $workedMinutes = AttendanceDailyLedger::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('worked_minutes');

        return [
            'is_locked' => $lockedSummaryRows > 0 || ($totalLedgers > 0 && $lockedLedgers === $totalLedgers),
            'total_ledgers' => $totalLedgers,
            'locked_ledgers' => $lockedLedgers,
            'summary_rows' => $summaryRows,
            'locked_summary_rows' => $lockedSummaryRows,
            'worked_hours' => round(((int) $workedMinutes) / 60, 1),
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    private function periodBounds(int $year, int $month): array
    {
        $from = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        return [$from, $to];
    }

    private function forgetPeriodLock(int $year, int $month): void
    {
        unset(self::$periodLockMemo[$this->periodKey($year, $month)]);
    }

    private function periodKey(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }
}
