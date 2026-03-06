<?php

namespace App\Modules\Attendance\Console\Commands;

use App\Modules\Attendance\Application\Services\AttendancePunchProcessingPipelineService;
use App\Modules\Attendance\Jobs\ProcessAttendancePunchesJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AttendanceProcessPunchesCommand extends Command
{
    protected $signature = 'attendance:punches:process
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}
        {--source= : Optional source filter}
        {--queue : Dispatch to queue instead of running sync}';

    protected $description = 'Normalize punches, match in/out pairs and generate daily attendance ledger';

    public function handle(AttendancePunchProcessingPipelineService $pipeline): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Attendance core tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $defaultHours = (int) config('attendance.processing.default_window_hours', 48);
        $from = $this->option('from')
            ? Carbon::parse((string) $this->option('from'))->startOfDay()
            : now()->subHours(max(1, $defaultHours))->startOfDay();
        $to = $this->option('to')
            ? Carbon::parse((string) $this->option('to'))->endOfDay()
            : now()->endOfDay();
        $source = $this->option('source') ? (string) $this->option('source') : null;

        if ((bool) $this->option('queue')) {
            ProcessAttendancePunchesJob::dispatch(
                fromDate: $from->toDateString(),
                toDate: $to->toDateString(),
                source: $source
            );

            $this->info(sprintf(
                'Queued: attendance punches processing (%s -> %s).',
                $from->toDateString(),
                $to->toDateString()
            ));

            return self::SUCCESS;
        }

        $stats = $pipeline->process($from, $to, $source);

        $this->table(
            ['metric', 'value'],
            collect($stats)->map(fn ($value, $metric) => [$metric, (string) $value])->values()->all()
        );

        return self::SUCCESS;
    }

    private function hasRequiredTables(): bool
    {
        foreach ([
            'attendance_raw_punches',
            'attendance_daily_ledgers',
            'attendance_manual_entries',
            'attendance_shift_assignments',
            'attendance_shifts',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}

