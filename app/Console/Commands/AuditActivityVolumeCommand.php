<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditActivityVolumeCommand extends Command
{
    protected $signature = 'audit:activity-volume
        {--days=30 : Reporting window in days}
        {--json : Print JSON summary}';

    protected $description = 'Report activity log volume from the configured audit connection.';

    public function handle(): int
    {
        $connection = config('activitylog.database_connection') ?: config('database.default');
        $table = config('activitylog.table_name', 'activity_log');

        if (! Schema::connection($connection)->hasTable($table)) {
            $this->error("Activity log table [{$connection}.{$table}] does not exist.");

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);
        $query = DB::connection($connection)->table($table);
        $windowQuery = DB::connection($connection)->table($table)->where('created_at', '>=', $cutoff);

        $summary = [
            'connection' => $connection,
            'table' => $table,
            'days' => $days,
            'total_records' => (int) $query->count(),
            'window_records' => (int) $windowQuery->count(),
            'oldest_recorded_at' => $query->min('created_at'),
            'newest_recorded_at' => $query->max('created_at'),
            'average_per_day' => round($windowQuery->count() / $days, 2),
        ];

        $byLogName = DB::connection($connection)->table($table)
            ->select('log_name', DB::raw('count(*) as total'))
            ->where('created_at', '>=', $cutoff)
            ->groupBy('log_name')
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'log_name' => $row->log_name ?: '(empty)',
                'total' => (int) $row->total,
            ])
            ->all();

        $byEvent = DB::connection($connection)->table($table)
            ->select('event', DB::raw('count(*) as total'))
            ->where('created_at', '>=', $cutoff)
            ->groupBy('event')
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'event' => $row->event ?: '(empty)',
                'total' => (int) $row->total,
            ])
            ->all();

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'by_log_name' => $byLogName,
                'by_event' => $byEvent,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['metric', 'value'], collect($summary)->map(
            fn ($value, $metric) => [$metric, (string) $value]
        )->values()->all());

        $this->newLine();
        $this->table(['log_name', 'records'], $byLogName);
        $this->newLine();
        $this->table(['event', 'records'], $byEvent);

        return self::SUCCESS;
    }
}
