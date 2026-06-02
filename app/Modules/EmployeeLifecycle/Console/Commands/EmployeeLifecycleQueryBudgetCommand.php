<?php

namespace App\Modules\EmployeeLifecycle\Console\Commands;

use App\Modules\EmployeeLifecycle\Application\Services\LifecycleDashboardReadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EmployeeLifecycleQueryBudgetCommand extends Command
{
    protected $signature = 'employee-lifecycle:query-budget
        {--allow-empty : Return success when lifecycle dataset is empty}
        {--dashboard-budget= : Max query count for dashboard build}
        {--events-budget= : Max query count for filtered events}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Employee Lifecycle dashboard flows';

    public function handle(LifecycleDashboardReadService $service): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Employee Lifecycle tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $hasData = DB::table('employee_lifecycle_events')->exists()
            || DB::table('employee_lifecycle_plan_templates')->exists()
            || DB::table('employee_lifecycle_probation_reviews')->exists()
            || DB::table('employee_lifecycle_movements')->exists()
            || DB::table('employee_lifecycle_offboarding_cases')->exists();

        if (! $hasData && (bool) $this->option('allow-empty')) {
            return $this->outputPayload($this->skippedPayload('employee_lifecycle_dataset_empty'));
        }

        $budgets = [
            'dashboard_build' => max(1, (int) ($this->option('dashboard-budget') ?: config('employee_lifecycle.performance.query_budget.dashboard_build', 45))),
            'filtered_events_build' => max(1, (int) ($this->option('events-budget') ?: config('employee_lifecycle.performance.query_budget.filtered_events_build', 20))),
        ];

        $results = [
            $this->probe('dashboard_build', $budgets['dashboard_build'], fn () => $service->dashboard()),
            $this->probe('filtered_events_build', $budgets['filtered_events_build'], fn () => $service->events([
                'type' => 'onboarding',
                'status' => 'in_progress',
            ])),
        ];

        return $this->outputPayload($this->payload($results));
    }

    private function hasRequiredTables(): bool
    {
        foreach ([
            'employee_lifecycle_events',
            'employee_lifecycle_tasks',
            'employee_lifecycle_plan_templates',
            'employee_lifecycle_task_templates',
            'employee_lifecycle_probation_reviews',
            'employee_lifecycle_movements',
            'employee_lifecycle_offboarding_cases',
        ] as $table) {
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

    private function probe(string $flow, int $budget, callable $callback): array
    {
        $connection = DB::connection();
        $wasLogging = method_exists($connection, 'logging') ? (bool) $connection->logging() : false;

        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $startedAt = microtime(true);
        $status = 'ok';
        $error = null;

        try {
            $callback();
        } catch (Throwable $throwable) {
            $status = 'failed';
            $error = $throwable->getMessage();
        } finally {
            $queries = $connection->getQueryLog();
            if (! $wasLogging) {
                $connection->disableQueryLog();
            }
        }

        return [
            'flow' => $flow,
            'status' => $status,
            'queries' => count($queries),
            'budget' => $budget,
            'over_budget' => count($queries) > $budget,
            'elapsed_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'db_time_ms' => round((float) collect($queries)->sum(fn ($query) => (float) ($query['time'] ?? 0)), 2),
            'error' => $error,
        ];
    }
}
