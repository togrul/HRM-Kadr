<?php

namespace App\Modules\Attendance\Console\Commands;

use App\Modules\Attendance\Application\Services\AttendancePunchProcessingPipelineService;
use App\Modules\Attendance\Jobs\RecalculateAttendanceLedgersJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AttendanceRecalculateLedgersCommand extends Command
{
    protected $signature = 'attendance:recalculate
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}
        {--structure_id= : Optional structure scope}
        {--tabel_no=* : Optional tabel_no scope (repeatable)}
        {--source= : Optional source filter}
        {--queue : Dispatch to queue instead of running sync}';

    protected $description = 'Recalculate attendance ledgers for date range + optional structure scope';

    public function handle(AttendancePunchProcessingPipelineService $pipeline): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Attendance core tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $from = $this->option('from')
            ? Carbon::parse((string) $this->option('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $to = $this->option('to')
            ? Carbon::parse((string) $this->option('to'))->endOfDay()
            : now()->endOfMonth()->endOfDay();

        $structureId = $this->option('structure_id');
        $structureId = is_numeric($structureId) ? (int) $structureId : null;

        $tabelNos = collect((array) $this->option('tabel_no'))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->values()
            ->all();

        $source = $this->option('source') ? (string) $this->option('source') : null;

        if ((bool) $this->option('queue')) {
            RecalculateAttendanceLedgersJob::dispatch(
                fromDate: $from->toDateString(),
                toDate: $to->toDateString(),
                source: $source,
                structureId: $structureId,
                tabelNos: $tabelNos
            );

            $this->info(sprintf(
                'Queued: attendance recalculation (%s -> %s).',
                $from->toDateString(),
                $to->toDateString()
            ));

            return self::SUCCESS;
        }

        $stats = $pipeline->process(
            from: $from,
            to: $to,
            source: $source,
            options: [
                'include_processed' => true,
                'mark_processed' => false,
                'structure_id' => $structureId,
                'tabel_nos' => $tabelNos,
            ]
        );

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
            'attendance_calendars',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}

