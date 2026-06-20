<?php

namespace App\Modules\Reports\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Modules\Reports\Application\Services\ComparativeReportService;
use App\Modules\Reports\Application\Services\DynamicReportBuilderService;
use App\Modules\Reports\Application\Services\ReportsOverviewService;
use App\Modules\Reports\Application\Services\StandardReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportsQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'reports:query-budget
        {--year= : Target year}
        {--month= : Target month}
        {--allow-empty : Return success when reports dataset is empty}
        {--overview-budget= : Max query count for overview flow}
        {--standard-budget= : Max query count for standard reports flow}
        {--dynamic-budget= : Max query count for dynamic builder flow}
        {--comparisons-budget= : Max query count for comparison flow}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Reports overview, standard, dynamic, and comparison flows';

    public function handle(
        ReportsOverviewService $overview,
        StandardReportService $standard,
        DynamicReportBuilderService $dynamic,
        ComparativeReportService $comparisons
    ): int {
        if (! $this->hasRequiredTables()) {
            $this->error('Reports tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $now = Carbon::now();
        $year = is_numeric($this->option('year')) ? (int) $this->option('year') : (int) $now->year;
        $month = is_numeric($this->option('month')) ? (int) $this->option('month') : (int) $now->month;

        $budgets = [
            'overview_build' => max(1, (int) ($this->option('overview-budget') ?: config('reports.performance.query_budget.overview_build', 12))),
            'standard_headcount_build' => max(1, (int) ($this->option('standard-budget') ?: config('reports.performance.query_budget.standard_headcount_build', 12))),
            'dynamic_build' => max(1, (int) ($this->option('dynamic-budget') ?: config('reports.performance.query_budget.dynamic_build', 12))),
            'comparisons_build' => max(1, (int) ($this->option('comparisons-budget') ?: config('reports.performance.query_budget.comparisons_build', 12))),
        ];

        $hasData = DB::table('personnels')->exists()
            || DB::table('attendance_daily_structure_summaries')->exists()
            || DB::table('training_delivery_records')->exists()
            || DB::table('performance_forms')->exists();

        if (! $hasData && (bool) $this->option('allow-empty')) {
            return $this->emitPayload([
                'summary' => [
                    'failed_probes' => 0,
                    'over_budget_probes' => 0,
                    'passed_probes' => 0,
                    'skipped' => true,
                    'reason' => 'reports_dataset_empty',
                ],
                'results' => [],
            ]);
        }

        $results = [];
        $results[] = $this->probe('overview_build', $budgets['overview_build'], fn () => $overview->build($year, $month));
        $results[] = $this->probe('standard_headcount_build', $budgets['standard_headcount_build'], fn () => $standard->build('headcount', ['year' => $year, 'month' => $month]));
        $results[] = $this->probe('dynamic_build', $budgets['dynamic_build'], fn () => $dynamic->build('attendance', 'structure', 'worked_hours', ['year' => $year, 'month' => $month]));
        $results[] = $this->probe('comparisons_build', $budgets['comparisons_build'], fn () => $comparisons->build($year, $month));

        $summary = [
            'year' => $year,
            'month' => $month,
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
        ];

        return $this->emitPayload([
            'summary' => $summary,
            'results' => $results,
        ]);
    }

    private function hasRequiredTables(): bool
    {
        foreach ([
            'personnels',
            'structures',
            'attendance_daily_structure_summaries',
            'training_delivery_records',
            'performance_forms',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function emitPayload(array $payload): int
    {
        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['flow', 'status', 'queries', 'budget', 'over_budget', 'elapsed_ms', 'db_time_ms', 'error'],
                collect($payload['results'])->map(fn (array $result) => [
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

        return ((int) data_get($payload, 'summary.failed_probes', 0) === 0 && (int) data_get($payload, 'summary.over_budget_probes', 0) === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
