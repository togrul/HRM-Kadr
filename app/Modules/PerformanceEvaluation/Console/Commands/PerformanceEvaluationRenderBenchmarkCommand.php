<?php

namespace App\Modules\PerformanceEvaluation\Console\Commands;

use App\Console\Support\AbstractRenderBenchmarkCommand;
use App\Models\PerformanceForm;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Livewire\EvaluationsSummary;
use App\Modules\PerformanceEvaluation\Livewire\EvaluatorScoreCapture;
use App\Modules\PerformanceEvaluation\Livewire\EvaluatorWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Overview;
use App\Modules\PerformanceEvaluation\Livewire\TestsSummary;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PerformanceEvaluationRenderBenchmarkCommand extends AbstractRenderBenchmarkCommand
{
    protected $signature = 'performance-evaluation:render-benchmark
        {--allow-empty : Return success when no performance dataset exists}
        {--overview-response-budget= : Max response size for overview render}
        {--overview-render-budget= : Max render time in ms for overview render}
        {--evaluations-summary-response-budget= : Max response size for evaluations summary render}
        {--evaluations-summary-render-budget= : Max render time in ms for evaluations summary render}
        {--tests-summary-response-budget= : Max response size for tests summary render}
        {--tests-summary-render-budget= : Max render time in ms for tests summary render}
        {--workspace-response-budget= : Max response size for evaluator workspace render}
        {--workspace-render-budget= : Max render time in ms for evaluator workspace render}
        {--score-capture-response-budget= : Max response size for score capture render}
        {--score-capture-render-budget= : Max render time in ms for score capture render}
        {--score-open-response-budget= : Max response size for opening score capture}
        {--score-open-render-budget= : Max render time in ms for opening score capture}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Performance Evaluation flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Performance Evaluation tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $hasData = DB::table('performance_cycles')->exists()
            || DB::table('performance_form_templates')->exists()
            || DB::table('performance_test_banks')->exists();

        if (! $hasData && (bool) $this->option('allow-empty')) {
            $payload = [
                'summary' => [
                    'failed_probes' => 0,
                    'over_budget_probes' => 0,
                    'passed_probes' => 0,
                    'skipped_probes' => 0,
                    'skipped' => true,
                    'reason' => 'performance_evaluation_dataset_empty',
                ],
                'results' => [],
            ];

            $this->outputPayload($payload);

            return self::SUCCESS;
        }

        $user = $this->resolveObserverUser();
        if (! $user) {
            $this->error('No user with Performance Evaluation access was found for render benchmarking.');

            return self::FAILURE;
        }

        $assignedFormId = (int) (PerformanceForm::query()
            ->where(function ($query) use ($user): void {
                $query->where('manager_id', $user->id)
                    ->orWhere('hr_reviewer_id', $user->id);
            })
            ->value('id') ?? 0);
        $scoreCaptureFormCatalog = $assignedFormId > 0
            ? $this->resolveScoreCaptureFormCatalog($user, $assignedFormId)
            : [];
        $budgets = [
            'overview_render' => $this->budgetPair('overview_render', 'overview'),
            'evaluations_summary_render' => $this->budgetPair('evaluations_summary_render', 'evaluations_summary'),
            'tests_summary_render' => $this->budgetPair('tests_summary_render', 'tests_summary'),
            'evaluator_workspace_render' => $this->budgetPair('evaluator_workspace_render', 'workspace'),
            'score_capture_render' => $this->budgetPair('score_capture_render', 'score_capture'),
            'evaluator_open_score_form_update' => $this->budgetPair('evaluator_open_score_form_update', 'score_open'),
        ];

        $results = [];
        $results[] = $this->probe('overview_render', $budgets['overview_render'], fn () => $profiler->measureRender($user, Overview::class));
        $results[] = $this->probe('evaluations_summary_render', $budgets['evaluations_summary_render'], fn () => $profiler->measureRender($user, EvaluationsSummary::class));
        $results[] = $this->probe('tests_summary_render', $budgets['tests_summary_render'], fn () => $profiler->measureRender($user, TestsSummary::class));
        $results[] = $this->probe('evaluator_workspace_render', $budgets['evaluator_workspace_render'], fn () => $profiler->measureRender($user, EvaluatorWorkspace::class));
        $results[] = $this->probe('score_capture_render', $budgets['score_capture_render'], fn () => $profiler->measureRender(
            $user,
            EvaluatorScoreCapture::class,
            ['formCatalog' => $scoreCaptureFormCatalog]
        ));
        $results[] = $assignedFormId > 0
            ? $this->probe('evaluator_open_score_form_update', $budgets['evaluator_open_score_form_update'], fn () => $profiler->measureInteraction(
                $user,
                EvaluatorScoreCapture::class,
                fn ($test) => $test->call('startScoreForm', $assignedFormId),
                ['formCatalog' => $scoreCaptureFormCatalog],
            ))
            : $this->skipped('evaluator_open_score_form_update', 'assigned_performance_form_missing');

        $summary = [
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->count(),
            'skipped_probes' => collect($results)->where('status', 'skipped')->count(),
        ];

        $payload = ['summary' => $summary, 'results' => $results];
        $this->outputPayload($payload);

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0) ? self::SUCCESS : self::FAILURE;
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

    private function resolveObserverUser(): ?User
    {
        $candidateIds = collect([
            DB::table('performance_forms')->whereNotNull('manager_id')->value('manager_id'),
            DB::table('performance_forms')->whereNotNull('hr_reviewer_id')->value('hr_reviewer_id'),
            DB::table('performance_test_sessions')->whereNotNull('reviewer_id')->value('reviewer_id'),
        ])->filter()->unique()->values();

        foreach ($candidateIds as $candidateId) {
            $user = User::query()->find($candidateId);

            if ($user?->canAny([
                'show-performance-evaluation',
                'manage-performance-evaluation',
                'review-performance-evaluation',
            ])) {
                return $user;
            }
        }

        return User::query()
            ->orderBy('id')
            ->cursor()
            ->first(fn (User $user): bool => $user->canAny([
                'show-performance-evaluation',
                'manage-performance-evaluation',
                'review-performance-evaluation',
            ]));
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function resolveScoreCaptureFormCatalog(User $user, int $assignedFormId): array
    {
        $form = PerformanceForm::query()
            ->with([
                'personnel:id,surname,name,patronymic,tabel_no',
                'template:id,name,code',
            ])
            ->find($assignedFormId);

        if (! $form) {
            return [];
        }

        return [[
            'id' => (int) $form->id,
            'label' => ($form->personnel?->fullname ?? '-').' / '.($form->template?->name ?: $form->template?->code ?: '-'),
            'template_id' => (int) $form->performance_form_template_id,
            'evaluator_type' => (int) $form->manager_id === (int) $user->id ? 'manager' : 'hr',
        ]];
    }

    /**
     * @return array<string, float|int|string|null>
     */
    private function skipped(string $flow, string $reason): array
    {
        return [
            'flow' => $flow,
            'status' => 'skipped',
            'render_ms' => null,
            'response_bytes' => null,
            'html_bytes' => null,
            'snapshot_bytes' => null,
            'effects_bytes' => null,
            'budget' => null,
            'over_budget' => false,
            'exceeded' => [],
            'error' => $reason,
        ];
    }

    private function budgetPair(string $flow, string $optionPrefix): array
    {
        $optionPrefix = str_replace('_', '-', $optionPrefix);
        $responseBudget = (int) ($this->option($optionPrefix.'-response-budget') ?: config("performance_evaluation.performance.render_budget.$flow.response_bytes", 150000));
        $renderBudget = (float) ($this->option($optionPrefix.'-render-budget') ?: config("performance_evaluation.performance.render_budget.$flow.render_ms", 100));

        return [
            'response_bytes' => max(1, $responseBudget),
            'render_ms' => max(1, $renderBudget),
        ];
    }

    private function outputPayload(array $payload): void
    {
        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return;
        }

        $this->table(
            ['flow', 'status', 'render_ms', 'response_bytes', 'budget_response', 'budget_render_ms', 'over_budget', 'html_bytes', 'snapshot_bytes', 'effects_bytes', 'error'],
            collect($payload['results'])->map(fn (array $result) => [
                $result['flow'],
                $result['status'],
                $result['render_ms'],
                $result['response_bytes'],
                data_get($result, 'budget.response_bytes'),
                data_get($result, 'budget.render_ms'),
                $result['over_budget'] ? implode(',', $result['exceeded']) : 'no',
                $result['html_bytes'],
                $result['snapshot_bytes'],
                $result['effects_bytes'],
                $result['error'] ?? '-',
            ])->all()
        );
    }
}
