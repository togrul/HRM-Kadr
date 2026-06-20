<?php

namespace App\Console\Commands;

use App\Modules\Candidates\Application\Services\CandidateAtsCompletionService;
use App\Modules\Compliance\Application\Services\DocumentExpiryReadService;
use App\Modules\EmployeeLifecycle\Application\Services\LifecycleDashboardReadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class HrStrategicQueryBudgetCommand extends Command
{
    protected $signature = 'hr:strategic-query-budget
        {--lifecycle-budget=45 : Max query count for lifecycle dashboard service}
        {--compliance-budget=35 : Max query count for compliance dashboard service}
        {--ats-budget=15 : Max query count for ATS requisition aging service}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget probes for strategic HR modules: lifecycle, compliance and ATS.';

    public function handle(
        LifecycleDashboardReadService $lifecycleService,
        DocumentExpiryReadService $complianceService,
        CandidateAtsCompletionService $atsService
    ): int {
        $results = [
            $this->probe('employee_lifecycle_dashboard', (int) $this->option('lifecycle-budget'), fn () => $lifecycleService->dashboard()),
            $this->probe('document_compliance_dashboard', (int) $this->option('compliance-budget'), fn () => $complianceService->dashboard()),
            $this->probe('candidate_ats_requisition_aging', (int) $this->option('ats-budget'), fn () => $atsService->requisitionAging()),
        ];

        $summary = [
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
        ];

        $payload = [
            'summary' => $summary,
            'results' => $results,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['flow', 'status', 'queries', 'budget', 'over_budget', 'elapsed_ms', 'db_time_ms', 'error'],
                collect($results)->map(fn (array $result): array => [
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

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function probe(string $flow, int $budget, callable $callback): array
    {
        $queries = 0;
        $dbTimeMs = 0.0;
        $startedAt = microtime(true);

        DB::listen(function ($query) use (&$queries, &$dbTimeMs): void {
            $queries++;
            $dbTimeMs += (float) $query->time;
        });

        try {
            $callback();
            $status = 'ok';
            $error = null;
        } catch (Throwable $exception) {
            $status = 'failed';
            $error = $exception->getMessage();
        }

        return [
            'flow' => $flow,
            'status' => $status,
            'queries' => $queries,
            'budget' => $budget,
            'over_budget' => $queries > $budget,
            'elapsed_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'db_time_ms' => round($dbTimeMs, 2),
            'error' => $error,
        ];
    }
}
