<?php

namespace App\Console\Commands;

use App\Models\OrderGenerationLog;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderTemplateMetricsReport extends Command
{
    protected $signature = 'orders:templates:metrics
        {--days=30 : Time window in days}
        {--order-type= : Limit to specific order_type_id}
        {--max-error-rate= : Fail when generation_error_rate_pct is above this threshold}
        {--max-p95= : Fail when p95 render duration (ms) is above this threshold}
        {--max-p99= : Fail when p99 render duration (ms) is above this threshold}
        {--min-total=1 : Minimum total samples required before threshold gates are evaluated}
        {--json : Print report as JSON}';

    protected $description = 'Show template generation metrics (error rate, slow percentiles, version usage)';

    public function handle(): int
    {
        if (! Schema::hasTable('order_generation_logs')) {
            $this->error('order_generation_logs table is missing. Run migrations first.');

            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));
        $orderTypeId = (int) ($this->option('order-type') ?? 0);
        $from = now()->subDays($days);

        $baseQuery = OrderGenerationLog::query()
            ->where('created_at', '>=', $from)
            ->when($orderTypeId > 0, fn ($query) => $query->where('order_type_id', $orderTypeId));

        $total = (clone $baseQuery)->count();
        $success = (clone $baseQuery)->where('status', 'success')->count();
        $failed = (clone $baseQuery)->where('status', 'failed')->count();
        $started = (clone $baseQuery)->where('status', 'started')->count();

        $durations = (clone $baseQuery)
            ->where('status', 'success')
            ->whereNotNull('duration_ms')
            ->orderBy('duration_ms')
            ->pluck('duration_ms')
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value >= 0)
            ->values()
            ->all();

        $versionUsageRows = (clone $baseQuery)
            ->select([
                'order_template_version_id',
                'order_type_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count"),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count"),
                DB::raw('AVG(duration_ms) as avg_duration_ms'),
            ])
            ->groupBy('order_template_version_id', 'order_type_id')
            ->orderByDesc('total_count')
            ->limit(20)
            ->get();

        $versionIds = $versionUsageRows
            ->pluck('order_template_version_id')
            ->filter(fn ($value) => is_numeric($value) && (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
        $orderTypeIds = $versionUsageRows
            ->pluck('order_type_id')
            ->filter(fn ($value) => is_numeric($value) && (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $versions = OrderTemplateVersion::query()
            ->whereIn('id', $versionIds)
            ->get(['id', 'version_no'])
            ->keyBy('id');
        $orderTypes = OrderType::query()
            ->whereIn('id', $orderTypeIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $usage = $versionUsageRows->map(function ($row) use ($versions, $orderTypes): array {
            $versionId = is_numeric($row->order_template_version_id) ? (int) $row->order_template_version_id : null;
            $typeId = is_numeric($row->order_type_id) ? (int) $row->order_type_id : null;
            $versionNo = $versionId ? (int) ($versions->get($versionId)?->version_no ?? 0) : null;

            return [
                'order_template_version_id' => $versionId,
                'version_no' => $versionNo,
                'order_type_id' => $typeId,
                'order_type_name' => $typeId ? (string) ($orderTypes->get($typeId)?->name ?? '') : '',
                'total' => (int) ($row->total_count ?? 0),
                'success' => (int) ($row->success_count ?? 0),
                'failed' => (int) ($row->failed_count ?? 0),
                'avg_duration_ms' => round((float) ($row->avg_duration_ms ?? 0), 2),
            ];
        })->values();

        $summary = [
            'window_days' => $days,
            'from' => $from->toDateTimeString(),
            'to' => now()->toDateTimeString(),
            'order_type_filter' => $orderTypeId > 0 ? $orderTypeId : null,
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'started' => $started,
            'generation_error_rate_pct' => $total > 0 ? round(($failed / $total) * 100, 2) : 0.0,
            'slow_render_p95_ms' => $this->percentile($durations, 95),
            'slow_render_p99_ms' => $this->percentile($durations, 99),
            'slow_render_max_ms' => ! empty($durations) ? max($durations) : null,
            'version_usage_count' => $usage->count(),
        ];

        $thresholds = [
            'max_error_rate' => $this->resolveOptionalFloatOption('max-error-rate'),
            'max_p95' => $this->resolveOptionalFloatOption('max-p95'),
            'max_p99' => $this->resolveOptionalFloatOption('max-p99'),
            'min_total' => max(0, (int) ($this->option('min-total') ?? 1)),
        ];

        $gate = $this->evaluateGate($summary, $thresholds);

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'version_usage' => $usage->all(),
                'gate' => $gate,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $gate['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $this->table(
            ['metric', 'value'],
            collect($summary)->map(fn ($value, $key) => [$key, is_scalar($value) || $value === null ? (string) $value : json_encode($value)])->values()->all()
        );

        $this->newLine();
        $this->table(
            ['version_id', 'version_no', 'order_type_id', 'order_type_name', 'total', 'success', 'failed', 'avg_duration_ms'],
            $usage->map(fn (array $row) => [
                $row['order_template_version_id'] ?? '-',
                $row['version_no'] ?? '-',
                $row['order_type_id'] ?? '-',
                $row['order_type_name'] !== '' ? $row['order_type_name'] : '-',
                $row['total'],
                $row['success'],
                $row['failed'],
                $row['avg_duration_ms'],
            ])->all()
        );

        if (! $gate['ok']) {
            $this->newLine();
            $this->error('Quality gate failed: '.implode('; ', $gate['reasons']));
        }

        return $gate['ok'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param array<int,int> $values
     */
    private function percentile(array $values, int $percentile): ?int
    {
        if (empty($values)) {
            return null;
        }

        $p = max(1, min(99, $percentile));
        $index = (int) ceil(($p / 100) * count($values)) - 1;
        $index = max(0, min(count($values) - 1, $index));

        return (int) $values[$index];
    }

    private function resolveOptionalFloatOption(string $key): ?float
    {
        $raw = $this->option($key);
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    /**
     * @param array<string,mixed> $summary
     * @param array{max_error_rate:?float,max_p95:?float,max_p99:?float,min_total:int} $thresholds
     * @return array{ok:bool,reasons:array<int,string>,thresholds:array<string,mixed>}
     */
    private function evaluateGate(array $summary, array $thresholds): array
    {
        $total = (int) ($summary['total'] ?? 0);
        $reasons = [];

        if ($total < $thresholds['min_total']) {
            return [
                'ok' => true,
                'reasons' => [],
                'thresholds' => $thresholds + ['skipped' => true],
            ];
        }

        $errorRate = (float) ($summary['generation_error_rate_pct'] ?? 0.0);
        $p95 = $summary['slow_render_p95_ms'];
        $p99 = $summary['slow_render_p99_ms'];

        if ($thresholds['max_error_rate'] !== null && $errorRate > $thresholds['max_error_rate']) {
            $reasons[] = sprintf('error_rate %.2f%% > %.2f%%', $errorRate, $thresholds['max_error_rate']);
        }

        if ($thresholds['max_p95'] !== null && is_numeric($p95) && (float) $p95 > $thresholds['max_p95']) {
            $reasons[] = sprintf('p95 %.2fms > %.2fms', (float) $p95, $thresholds['max_p95']);
        }

        if ($thresholds['max_p99'] !== null && is_numeric($p99) && (float) $p99 > $thresholds['max_p99']) {
            $reasons[] = sprintf('p99 %.2fms > %.2fms', (float) $p99, $thresholds['max_p99']);
        }

        return [
            'ok' => empty($reasons),
            'reasons' => $reasons,
            'thresholds' => $thresholds + ['skipped' => false],
        ];
    }
}
