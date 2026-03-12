<?php

namespace App\Modules\TrainingNeeds\Console\Commands;

use App\Models\TrainingCompetency;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingNeedItem;
use App\Models\TrainingProgram;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedAnalyticsService;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedReportingService;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedSuggestionService;
use App\Modules\TrainingNeeds\Application\Services\TrainingSessionProposalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TrainingNeedsQueryBudgetCommand extends Command
{
    protected $signature = 'training-needs:query-budget
        {--year= : Target plan year}
        {--quarter= : Target quarter}
        {--allow-empty : Return success when no training dataset exists}
        {--overview-budget= : Max query count for overview flow}
        {--planning-budget= : Max query count for planning flow}
        {--calendar-budget= : Max query count for calendar/results flow}
        {--analytics-budget= : Max query count for analytics island}
        {--results-summary-budget= : Max query count for results summary island}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Training Needs dashboard flows';

    public function handle(
        TrainingNeedAnalyticsService $analyticsService,
        TrainingNeedReportingService $reportingService,
        TrainingNeedSuggestionService $suggestionService,
        TrainingSessionProposalService $proposalService,
    ): int {
        if (! $this->hasRequiredTables()) {
            $this->error('Training Needs tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $year = is_numeric($this->option('year')) ? (int) $this->option('year') : (int) now()->format('Y');
        $quarter = is_numeric($this->option('quarter')) ? (int) $this->option('quarter') : null;

        $budgets = [
            'overview_build' => max(1, (int) ($this->option('overview-budget') ?: config('training_needs.performance.query_budget.overview_build', 20))),
            'planning_build' => max(1, (int) ($this->option('planning-budget') ?: config('training_needs.performance.query_budget.planning_build', 24))),
            'calendar_build' => max(1, (int) ($this->option('calendar-budget') ?: config('training_needs.performance.query_budget.calendar_build', 26))),
            'analytics_build' => max(1, (int) ($this->option('analytics-budget') ?: config('training_needs.performance.query_budget.analytics_build', 12))),
            'results_summary_build' => max(1, (int) ($this->option('results-summary-budget') ?: config('training_needs.performance.query_budget.results_summary_build', 14))),
        ];

        $hasData = DB::table('training_competencies')->exists()
            || DB::table('training_need_items')->exists()
            || DB::table('training_sessions')->exists();

        if (! $hasData && (bool) $this->option('allow-empty')) {
            $payload = [
                'summary' => [
                    'failed_probes' => 0,
                    'over_budget_probes' => 0,
                    'passed_probes' => 0,
                    'skipped' => true,
                    'reason' => 'training_needs_dataset_empty',
                ],
                'results' => [],
            ];

            $this->outputPayload($payload);

            return self::SUCCESS;
        }

        $results = [];
        $results[] = $this->probe('overview_build', $budgets['overview_build'], function (): void {
            DB::selectOne(
                'select
                    (select count(*) from training_competency_groups) as `groups`,
                    (select count(*) from training_levels) as `levels`,
                    (select count(*) from training_competencies) as `competencies`,
                    (select count(*) from training_programs) as `programs`,
                    (select count(*) from training_program_competency_map) as `program_maps`,
                    (select count(*) from role_competency_requirements) as `requirements`,
                    (select count(*) from employee_competency_profiles) as `profiles`,
                    (select count(*) from training_need_items) as `needs`'
            );

            TrainingCompetency::query()->with('group:id,name')->latest('id')->limit(5)->get();
            TrainingProgram::query()->latest('id')->limit(5)->get();
            TrainingNeedItem::query()->with(['personnel:id,tabel_no,surname,name,patronymic', 'competency:id,name'])->latest('id')->limit(8)->get();
        });
        $results[] = $this->probe('planning_build', $budgets['planning_build'], function () use ($analyticsService, $suggestionService, $proposalService, $year, $quarter): void {
            $analyticsService->summary();
            $analyticsService->sourceMix();
            $analyticsService->priorityMix();
            $analyticsService->recentPlans();
            $suggestionService->suggestions($year, $quarter, 6);
            $proposalService->proposals(6);
        });
        $results[] = $this->probe('calendar_build', $budgets['calendar_build'], function () use ($analyticsService, $reportingService): void {
            $reportingService->upcomingSessions();
            $reportingService->feedbackSessionSummaries();
            $analyticsService->deliverySummary();
            $reportingService->deliveryRows();
            $reportingService->feedbackRows();
        });
        $results[] = $this->probe('analytics_build', $budgets['analytics_build'], function () use ($analyticsService): void {
            $analyticsService->summary();
            $analyticsService->sourceMix();
            $analyticsService->priorityMix();
            $analyticsService->topGapPositions();
        });
        $results[] = $this->probe('results_summary_build', $budgets['results_summary_build'], function () use ($analyticsService, $reportingService): void {
            TrainingFeedbackForm::query()->with(['session:id,title', 'responses'])->latest('id')->limit(6)->get();
            $reportingService->feedbackSessionSummaries();
            $analyticsService->deliverySummary();
            $reportingService->deliveryRows();
            $reportingService->feedbackRows();
            $reportingService->auditRows();
        });

        $summary = [
            'year' => $year,
            'quarter' => $quarter,
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
        ];

        $payload = ['summary' => $summary, 'results' => $results];
        $this->outputPayload($payload);

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function hasRequiredTables(): bool
    {
        foreach (['training_competencies', 'training_need_items', 'training_annual_plans', 'training_sessions'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function outputPayload(array $payload): void
    {
        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return;
        }

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

        $queryCount = count($queries);
        $dbTimeMs = round((float) collect($queries)->sum(fn ($query) => (float) ($query['time'] ?? 0)), 2);
        $elapsedMs = round((microtime(true) - $startedAt) * 1000, 2);

        return [
            'flow' => $flow,
            'status' => $status,
            'queries' => $queryCount,
            'budget' => $budget,
            'over_budget' => $queryCount > $budget,
            'elapsed_ms' => $elapsedMs,
            'db_time_ms' => $dbTimeMs,
            'error' => $error,
        ];
    }
}
