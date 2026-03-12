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
        {--evaluations-summary-budget= : Max query count for evaluations summary island}
        {--tests-summary-budget= : Max query count for tests summary island}
        {--evaluator-workspace-budget= : Max query count for evaluator workspace flow}
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
            'evaluations_summary_build' => max(1, (int) ($this->option('evaluations-summary-budget') ?: config('performance_evaluation.performance.query_budget.evaluations_summary_build', 8))),
            'tests_summary_build' => max(1, (int) ($this->option('tests-summary-budget') ?: config('performance_evaluation.performance.query_budget.tests_summary_build', 10))),
            'evaluator_workspace_build' => max(1, (int) ($this->option('evaluator-workspace-budget') ?: config('performance_evaluation.performance.query_budget.evaluator_workspace_build', 12))),
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
        $results[] = $this->probe('evaluations_summary_build', $budgets['evaluations_summary_build'], function (): void {
            PerformanceForm::query()
                ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
                ->leftJoin('performance_form_templates', 'performance_form_templates.id', '=', 'performance_forms.performance_form_template_id')
                ->leftJoin('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
                ->leftJoin('users as manager_users', 'manager_users.id', '=', 'performance_forms.manager_id')
                ->leftJoin('users as hr_users', 'hr_users.id', '=', 'performance_forms.hr_reviewer_id')
                ->select([
                    'performance_forms.id',
                    DB::raw('performance_cycles.name as cycle_name'),
                    DB::raw('performance_form_templates.name as template_name'),
                    DB::raw("CONCAT(personnels.surname, ' ', personnels.name, ' ', personnels.patronymic) as personnel_fullname"),
                    DB::raw('manager_users.name as manager_name'),
                    DB::raw('hr_users.name as hr_reviewer_name'),
                ])
                ->latest('performance_forms.id')
                ->limit(6)
                ->get();
        });
        $results[] = $this->probe('tests_summary_build', $budgets['tests_summary_build'], function (): void {
            PerformanceTestBank::query()->withCount('questions')->latest('id')->limit(5)->get();
            PerformanceTestAttempt::query()->with(['session.bank:id,name', 'session.personnel:id,tabel_no,surname,name,patronymic'])->latest('id')->limit(6)->get();
            PerformanceTestAttemptAnswer::query()
                ->leftJoin('performance_test_questions', 'performance_test_questions.id', '=', 'performance_test_attempt_answers.performance_test_question_id')
                ->leftJoin('performance_test_attempts', 'performance_test_attempts.id', '=', 'performance_test_attempt_answers.performance_test_attempt_id')
                ->leftJoin('performance_test_sessions', 'performance_test_sessions.id', '=', 'performance_test_attempts.performance_test_session_id')
                ->leftJoin('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
                ->select([
                    'performance_test_attempt_answers.id',
                    DB::raw("CONCAT(personnels.surname, ' ', personnels.name, ' ', personnels.patronymic) as personnel_fullname"),
                    DB::raw('performance_test_questions.prompt as question_prompt'),
                    DB::raw('performance_test_questions.question_type as question_type_name'),
                ])
                ->where('review_status', 'pending')
                ->latest('performance_test_attempt_answers.id')
                ->limit(6)
                ->get();
        });
        $results[] = $this->probe('evaluator_workspace_build', $budgets['evaluator_workspace_build'], function (): void {
            $reviewerId = (int) (DB::table('performance_forms')->value('manager_id')
                ?: DB::table('performance_forms')->value('hr_reviewer_id')
                ?: DB::table('performance_test_sessions')->value('reviewer_id')
                ?: 0);

            $forms = PerformanceForm::query()
                ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
                ->leftJoin('performance_form_templates', 'performance_form_templates.id', '=', 'performance_forms.performance_form_template_id')
                ->leftJoin('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
                ->leftJoin('users as manager_users', 'manager_users.id', '=', 'performance_forms.manager_id')
                ->leftJoin('users as hr_users', 'hr_users.id', '=', 'performance_forms.hr_reviewer_id')
                ->select([
                    'performance_forms.*',
                    'performance_cycles.name as cycle_name',
                    'performance_form_templates.name as template_name',
                    'performance_form_templates.code as template_code',
                    'personnels.surname as personnel_surname',
                    'personnels.name as personnel_name',
                    'personnels.patronymic as personnel_patronymic',
                    'personnels.tabel_no as personnel_tabel_no',
                    'manager_users.name as manager_name',
                    'hr_users.name as hr_reviewer_name',
                ])
                ->when($reviewerId > 0, function ($query) use ($reviewerId): void {
                    $query->where(function ($inner) use ($reviewerId): void {
                        $inner->where('manager_id', $reviewerId)
                            ->orWhere('hr_reviewer_id', $reviewerId);
                    });
                })
                ->latest('id')
                ->limit(24)
                ->get();

            $firstForm = $forms->first();

            if ($firstForm) {
                $templateId = DB::table('performance_forms')
                    ->where('id', $firstForm->id)
                    ->value('performance_form_template_id');
                $firstItemId = DB::table('performance_form_template_items')
                    ->join('performance_form_template_sections', 'performance_form_template_sections.id', '=', 'performance_form_template_items.performance_form_template_section_id')
                    ->where('performance_form_template_sections.performance_form_template_id', $templateId)
                    ->orderBy('performance_form_template_items.sort_order')
                    ->orderBy('performance_form_template_items.name')
                    ->value('performance_form_template_items.id');

                if ($firstItemId) {
                    $evaluatorType = (int) $firstForm->manager_id === $reviewerId ? 'manager' : 'hr';

                    DB::table('performance_form_scores')
                        ->where('performance_form_id', $firstForm->id)
                        ->where('performance_form_template_item_id', $firstItemId)
                        ->where('evaluator_type', $evaluatorType)
                        ->first();
                }
            }

            PerformanceTestAttemptAnswer::query()
                ->leftJoin('performance_test_attempts', 'performance_test_attempts.id', '=', 'performance_test_attempt_answers.performance_test_attempt_id')
                ->leftJoin('performance_test_sessions', 'performance_test_sessions.id', '=', 'performance_test_attempts.performance_test_session_id')
                ->leftJoin('personnels', 'personnels.id', '=', 'performance_test_sessions.personnel_id')
                ->leftJoin('performance_test_banks', 'performance_test_banks.id', '=', 'performance_test_sessions.performance_test_bank_id')
                ->leftJoin('performance_test_questions', 'performance_test_questions.id', '=', 'performance_test_attempt_answers.performance_test_question_id')
                ->select([
                    'performance_test_attempt_answers.id',
                    'performance_test_attempts.id as attempt_id',
                    'performance_test_questions.prompt as question_prompt',
                    'performance_test_questions.question_type as question_type_name',
                    'performance_test_questions.max_score as question_max_score',
                    'performance_test_banks.name as bank_name',
                ])
                ->when($reviewerId > 0, fn ($query) => $query->where('performance_test_sessions.reviewer_id', $reviewerId))
                ->where('review_status', 'pending')
                ->latest('performance_test_attempt_answers.id')
                ->limit(24)
                ->get();
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
