<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OrderTemplateReportCommand extends Command
{
    protected $signature = 'orders:templates:report
        {--days=1 : Time window in days for metrics}
        {--order-type= : Optional order_type_id filter}
        {--order-no= : Optional order_no for query-budget probe}
        {--allow-empty-budget : Allow query-budget to pass when reference order is missing}
        {--channel=* : Report channels (log,slack,telegram)}
        {--json : Print final payload as JSON}';

    protected $description = 'Send template metrics/query-budget report to configured channels';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?? 1));
        $orderType = is_numeric($this->option('order-type')) ? (int) $this->option('order-type') : null;
        $orderNo = trim((string) ($this->option('order-no') ?? ''));

        $metricsArgs = [
            '--days' => $days,
            '--json' => true,
            '--min-total' => (int) config('orders.observability.reports.metrics_min_total', 1),
        ];

        if ($orderType !== null && $orderType > 0) {
            $metricsArgs['--order-type'] = $orderType;
        }

        $maxErrorRate = config('orders.observability.reports.metrics_max_error_rate');
        $maxP95 = config('orders.observability.reports.metrics_max_p95');
        $maxP99 = config('orders.observability.reports.metrics_max_p99');

        if ($maxErrorRate !== null && $maxErrorRate !== '') {
            $metricsArgs['--max-error-rate'] = (string) $maxErrorRate;
        }
        if ($maxP95 !== null && $maxP95 !== '') {
            $metricsArgs['--max-p95'] = (string) $maxP95;
        }
        if ($maxP99 !== null && $maxP99 !== '') {
            $metricsArgs['--max-p99'] = (string) $maxP99;
        }

        $metricsExit = Artisan::call('orders:templates:metrics', $metricsArgs);
        $metricsPayload = json_decode(Artisan::output(), true);

        if (! is_array($metricsPayload)) {
            $this->error('Could not parse metrics payload.');

            return self::FAILURE;
        }

        $budgetArgs = [
            '--json' => true,
        ];

        if ($orderType !== null && $orderType > 0) {
            $budgetArgs['--order-type'] = $orderType;
        }
        if ($orderNo !== '') {
            $budgetArgs['--order-no'] = $orderNo;
        }
        if ((bool) $this->option('allow-empty-budget')) {
            $budgetArgs['--allow-empty'] = true;
        }

        $queryBudgetExit = Artisan::call('orders:templates:query-budget', $budgetArgs);
        $queryBudgetPayload = json_decode(Artisan::output(), true);

        if (! is_array($queryBudgetPayload)) {
            $queryBudgetPayload = [
                'summary' => [
                    'failed_probes' => 1,
                    'over_budget_probes' => 0,
                    'passed_probes' => 0,
                    'parse_error' => true,
                ],
                'results' => [],
            ];
            $queryBudgetExit = self::FAILURE;
        }

        $reportPayload = [
            'generated_at' => now()->toDateTimeString(),
            'window_days' => $days,
            'order_type' => $orderType,
            'metrics_exit_code' => $metricsExit,
            'query_budget_exit_code' => $queryBudgetExit,
            'metrics' => $metricsPayload,
            'query_budget' => $queryBudgetPayload,
        ];

        $summaryText = $this->renderSummaryText($reportPayload);

        $channels = $this->resolveChannels();
        $channelResults = [];
        foreach ($channels as $channel) {
            $channelResults[$channel] = $this->sendToChannel($channel, $summaryText, $reportPayload);
        }

        $reportPayload['channels'] = $channelResults;

        if ((bool) $this->option('json')) {
            $this->line(json_encode($reportPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $failedChannels = collect($channelResults)->filter(fn ($row) => ($row['status'] ?? '') === 'failed')->count();

        return ($metricsExit === self::SUCCESS && $queryBudgetExit === self::SUCCESS && $failedChannels === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * @param array<string,mixed> $reportPayload
     */
    private function renderSummaryText(array $reportPayload): string
    {
        $metricsSummary = (array) data_get($reportPayload, 'metrics.summary', []);
        $gate = (array) data_get($reportPayload, 'metrics.gate', []);
        $budgetSummary = (array) data_get($reportPayload, 'query_budget.summary', []);

        $gateStatus = data_get($gate, 'ok') === true ? 'PASS' : 'FAIL';
        $gateReasons = (array) data_get($gate, 'reasons', []);

        return implode("\n", [
            'Orders Template Report',
            'Generated: '.(string) ($reportPayload['generated_at'] ?? now()->toDateTimeString()),
            'Window (days): '.(string) ($reportPayload['window_days'] ?? '-'),
            'Order type: '.(string) (($reportPayload['order_type'] ?? null) ?: 'all'),
            '',
            'Metrics:',
            '- total: '.(string) ($metricsSummary['total'] ?? 0),
            '- success: '.(string) ($metricsSummary['success'] ?? 0),
            '- failed: '.(string) ($metricsSummary['failed'] ?? 0),
            '- error_rate_pct: '.(string) ($metricsSummary['generation_error_rate_pct'] ?? 0),
            '- p95_ms: '.(string) ($metricsSummary['slow_render_p95_ms'] ?? '-'),
            '- p99_ms: '.(string) ($metricsSummary['slow_render_p99_ms'] ?? '-'),
            '- gate: '.$gateStatus.(empty($gateReasons) ? '' : ' ('.implode(', ', $gateReasons).')'),
            '',
            'Query budget:',
            '- passed_probes: '.(string) ($budgetSummary['passed_probes'] ?? 0),
            '- failed_probes: '.(string) ($budgetSummary['failed_probes'] ?? 0),
            '- over_budget_probes: '.(string) ($budgetSummary['over_budget_probes'] ?? 0),
        ]);
    }

    /**
     * @return array<int,string>
     */
    private function resolveChannels(): array
    {
        $channels = array_filter(array_map('trim', (array) $this->option('channel')));

        if (empty($channels)) {
            $channels = (array) config('orders.observability.reports.channels', ['log']);
        }

        return array_values(array_unique(array_filter(array_map('strtolower', $channels))));
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{status:string,message:string}
     */
    private function sendToChannel(string $channel, string $summaryText, array $payload): array
    {
        return match ($channel) {
            'log' => $this->sendToLog($payload),
            'slack' => $this->sendToSlack($summaryText),
            'telegram' => $this->sendToTelegram($summaryText),
            default => ['status' => 'failed', 'message' => "Unsupported channel: {$channel}"],
        };
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{status:string,message:string}
     */
    private function sendToLog(array $payload): array
    {
        try {
            $logFile = (string) config('orders.observability.reports.log_file', 'logs/orders-template-metrics.log');
            Storage::disk('local')->append($logFile, json_encode($payload, JSON_UNESCAPED_UNICODE));

            return ['status' => 'ok', 'message' => 'written'];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status:string,message:string}
     */
    private function sendToSlack(string $summaryText): array
    {
        $webhook = trim((string) config('orders.observability.reports.slack_webhook', ''));
        if ($webhook === '') {
            return ['status' => 'failed', 'message' => 'ORDERS_TEMPLATE_REPORT_SLACK_WEBHOOK is missing'];
        }

        try {
            $response = Http::timeout(10)->post($webhook, ['text' => $summaryText]);
            if ($response->failed()) {
                return ['status' => 'failed', 'message' => 'Slack response: '.$response->status()];
            }

            return ['status' => 'ok', 'message' => 'sent'];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{status:string,message:string}
     */
    private function sendToTelegram(string $summaryText): array
    {
        $token = trim((string) config('orders.observability.reports.telegram_bot_token', ''));
        $chatId = trim((string) config('orders.observability.reports.telegram_chat_id', ''));

        if ($token === '' || $chatId === '') {
            return ['status' => 'failed', 'message' => 'ORDERS_TEMPLATE_REPORT_TELEGRAM_BOT_TOKEN/CHAT_ID missing'];
        }

        try {
            $response = Http::timeout(10)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $summaryText,
                ]);

            if ($response->failed()) {
                return ['status' => 'failed', 'message' => 'Telegram response: '.$response->status()];
            }

            return ['status' => 'ok', 'message' => 'sent'];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }
}
