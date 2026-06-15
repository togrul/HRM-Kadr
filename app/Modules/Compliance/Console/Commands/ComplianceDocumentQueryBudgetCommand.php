<?php

namespace App\Modules\Compliance\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Modules\Compliance\Application\Services\DocumentExpiryReadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ComplianceDocumentQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'compliance:document-query-budget
        {--allow-empty : Return success when personnel compliance dataset is empty}
        {--dashboard-budget= : Max query count for dashboard build}
        {--rows-budget= : Max query count for filtered rows}
        {--reminders-budget= : Max query count for reminder rows}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Compliance document-expiry flows';

    public function handle(DocumentExpiryReadService $service): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Compliance reference tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $hasData = DB::table('personnels')->whereNull('deleted_at')->exists();

        if (! $hasData && (bool) $this->option('allow-empty')) {
            return $this->outputPayload($this->skippedPayload('compliance_personnel_dataset_empty'));
        }

        $budgets = [
            'dashboard_build' => max(1, (int) ($this->option('dashboard-budget') ?: config('compliance.performance.query_budget.dashboard_build', 35))),
            'filtered_rows_build' => max(1, (int) ($this->option('rows-budget') ?: config('compliance.performance.query_budget.filtered_rows_build', 20))),
            'reminder_rows_build' => max(1, (int) ($this->option('reminders-budget') ?: config('compliance.performance.query_budget.reminder_rows_build', 20))),
        ];

        $results = [
            $this->probe('dashboard_build', $budgets['dashboard_build'], fn () => $service->dashboard()),
            $this->probe('filtered_rows_build', $budgets['filtered_rows_build'], fn () => $service->rows([
                'type' => 'passport',
                'status' => 'missing',
            ])),
            $this->probe('reminder_rows_build', $budgets['reminder_rows_build'], fn () => $service->reminderRows((int) config('compliance.document_expiry.reminders.days_ahead', 30))),
        ];

        return $this->outputPayload($this->payload($results));
    }

    private function hasRequiredTables(): bool
    {
        foreach (['personnels', 'structures', 'positions'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function skippedPayload(string $reason): array
    {
        return [
            'summary' => [
                'failed_probes' => 0,
                'over_budget_probes' => 0,
                'passed_probes' => 0,
                'skipped' => true,
                'reason' => $reason,
            ],
            'results' => [],
        ];
    }

    private function payload(array $results): array
    {
        return [
            'summary' => [
                'failed_probes' => collect($results)->where('status', 'failed')->count(),
                'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
                'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
            ],
            'results' => $results,
        ];
    }

    private function outputPayload(array $payload): int
    {
        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['flow', 'status', 'queries', 'budget', 'over_budget', 'elapsed_ms', 'db_time_ms', 'error'],
                collect($payload['results'])->map(fn (array $result): array => [
                    $result['flow'],
                    $result['status'],
                    $result['queries'],
                    $result['budget'],
                    $result['over_budget'] ? 'yes' : 'no',
                    $result['elapsed_ms'],
                    $result['db_time_ms'],
                    $result['error'] ?? '-',
                ])->all()
            );
        }

        return ($payload['summary']['failed_probes'] === 0 && $payload['summary']['over_budget_probes'] === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
