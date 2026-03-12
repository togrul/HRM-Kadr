<?php

namespace App\Modules\PerformanceEvaluation\Console\Commands;

use App\Models\PerformanceCycle;
use App\Models\PerformanceForm;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestBank;
use App\Models\PerformanceTrainingNeedLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PerformanceEvaluationQueryBudgetCommand extends Command
{
    protected $signature = 'performance-evaluation:query-budget
        {--allow-empty : Return success when no performance dataset exists}
        {--overview-budget= : Max query count for overview flow}
        {--templates-budget= : Max query count for templates/evaluations flow}
        {--tests-budget= : Max query count for tests flow}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Performance Evaluation dashboard flows';

    public function handle(): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Performance Evaluation tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $budgets = [
            'overview_build' => max(1, (int) ($this->option('overview-budget') ?: config('performance_evaluation.performance.query_budget.overview_build', 18))),
            'templates_build' => max(1, (int) ($this->option('templates-budget') ?: config('performance_evaluation.performance.query_budget.templates_build', 20))),
            'tests_build' => max(1, (int) ($this->option('tests-budget') ?: config('performance_evaluation.performance.query_budget.tests_build', 22))),
        ];

        $hasData = DB::table('performance_cycles')->exists()
            || DB::table('performance_form_templates')->exists()
            || DB::table('performance_test_banks')->exists();

        if (! $hasData && (bool) $this->option('allow-empty')) {
            $payload = [
                'summary' => [
                    'failed_probes' => 0,
                    'over_budget_probes' => 0,
                    'passed_probes' => 0,
                    'skipped' => true,
                    'reason' => 'performance_evaluation_dataset_empty',
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
                    (select count(*) from performance_cycles) as `cycles`,
                    (select count(*) from performance_form_templates) as `templates`,
                    (select count(*) from performance_form_template_sections) as `sections`,
                    (select count(*) from performance_form_template_items) as `items`,
                    (select count(*) from performance_forms) as `forms`,
                    (select count(*) from performance_form_scores) as `scores`,
                    (select count(*) from performance_training_need_links) as `links`'
            );

            PerformanceCycle::query()->latest('id')->limit(5)->get();
            PerformanceFormTemplate::query()->withCount('sections')->latest('id')->limit(5)->get();
            PerformanceForm::query()->with(['cycle:id,name', 'template:id,name,code', 'personnel:id,tabel_no,surname,name,patronymic'])->latest('id')->limit(6)->get();
            PerformanceTrainingNeedLink::query()->with(['trainingNeed:id,priority,status,reason', 'competency:id,name'])->latest('id')->limit(6)->get();
        });
        $results[] = $this->probe('templates_build', $budgets['templates_build'], function (): void {
            DB::table('performance_form_templates')->select('id', 'name as label')->orderBy('name')->limit(100)->get();
            DB::table('performance_form_template_sections')->select('id', 'name as label')->orderBy('name')->limit(100)->get();
            DB::table('training_competencies')->select('id', 'name as label')->where('is_active', 1)->orderBy('name')->limit(100)->get();
            DB::table('personnels')->selectRaw("id, CONCAT(surname, ' ', name, ' ', patronymic, ' (#', tabel_no, ')') as label")->orderBy('surname')->orderBy('name')->limit(100)->get();
        });
        $results[] = $this->probe('tests_build', $budgets['tests_build'], function (): void {
            PerformanceTestBank::query()->withCount('questions')->latest('id')->limit(5)->get();
            PerformanceTestAttempt::query()->with(['session.bank:id,name', 'session.personnel:id,tabel_no,surname,name,patronymic'])->latest('id')->limit(6)->get();
            PerformanceTestAttemptAnswer::query()->with(['question:id,prompt,question_type'])->where('review_status', 'pending')->latest('id')->limit(6)->get();
            DB::table('users')->selectRaw("id, COALESCE(NULLIF(name, ''), email) as label")->orderBy('name')->orderBy('email')->limit(100)->get();
        });

        $summary = [
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
        foreach (['performance_cycles', 'performance_form_templates', 'performance_test_banks'] as $table) {
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
