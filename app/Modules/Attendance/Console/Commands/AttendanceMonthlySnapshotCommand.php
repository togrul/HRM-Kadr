<?php

namespace App\Modules\Attendance\Console\Commands;

use App\Modules\Attendance\Application\Services\AttendanceMonthLockService;
use App\Modules\Attendance\Jobs\GenerateAttendanceMonthlySnapshotJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AttendanceMonthlySnapshotCommand extends Command
{
    protected $signature = 'attendance:monthly-snapshot
        {--year= : Target year}
        {--month= : Target month}
        {--previous-month : Use previous month relative to now}
        {--lock : Also lock month after snapshot}
        {--queue : Dispatch to queue instead of sync run}';

    protected $description = 'Generate attendance monthly summaries snapshot for payroll export';

    public function handle(AttendanceMonthLockService $service): int
    {
        $now = Carbon::now();
        if ((bool) $this->option('previous-month')) {
            $period = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $year = (int) $period->year;
            $month = (int) $period->month;
        } else {
            $year = (int) ($this->option('year') ?: $now->year);
            $month = (int) ($this->option('month') ?: $now->month);
        }
        $lock = (bool) $this->option('lock');

        if ($month < 1 || $month > 12) {
            $this->error('Month must be between 1 and 12.');

            return self::FAILURE;
        }

        if ((bool) $this->option('queue')) {
            GenerateAttendanceMonthlySnapshotJob::dispatch($year, $month, $lock);
            $this->info(sprintf('Queued monthly snapshot for %04d-%02d (lock=%s).', $year, $month, $lock ? 'yes' : 'no'));

            return self::SUCCESS;
        }

        $stats = $service->snapshotMonth($year, $month, $lock);
        $this->table(
            ['metric', 'value'],
            [
                ['year', (string) $year],
                ['month', (string) $month],
                ['lock', $lock ? 'yes' : 'no'],
                ['summary_upserts', (string) ($stats['summary_upserts'] ?? 0)],
                ['locked_ledgers', (string) ($stats['locked_ledgers'] ?? 0)],
            ]
        );

        return self::SUCCESS;
    }
}
