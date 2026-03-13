<?php

namespace App\Modules\TrainingNeeds\Console\Commands;

use App\Models\TrainingSession;
use App\Models\TrainingAnnualPlan;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Modules\TrainingNeeds\Livewire\Analytics;
use App\Modules\TrainingNeeds\Livewire\Dashboard;
use App\Modules\TrainingNeeds\Livewire\Reports;
use App\Modules\TrainingNeeds\Livewire\ResultsSummary;
use App\Modules\TrainingNeeds\Livewire\SessionDetailWorkspace;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TrainingNeedsRenderBenchmarkCommand extends Command
{
    protected $signature = 'training-needs:render-benchmark
        {--allow-empty : Return success when no training dataset exists}
        {--planning-response-budget= : Max initial response size for planning render}
        {--planning-render-budget= : Max render time in ms for planning render}
        {--calendar-response-budget= : Max initial response size for calendar render}
        {--calendar-render-budget= : Max render time in ms for calendar render}
        {--analytics-response-budget= : Max response size for analytics render}
        {--analytics-render-budget= : Max render time in ms for analytics render}
        {--results-summary-response-budget= : Max response size for results summary render}
        {--results-summary-render-budget= : Max render time in ms for results summary render}
        {--reports-response-budget= : Max response size for reports render}
        {--reports-render-budget= : Max render time in ms for reports render}
        {--session-detail-response-budget= : Max response size for session detail workspace render}
        {--session-detail-render-budget= : Max render time in ms for session detail workspace render}
        {--calendar-select-response-budget= : Max response size for selecting a session in calendar}
        {--calendar-select-render-budget= : Max render time in ms for selecting a session in calendar}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Training Needs flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Training Needs tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $hasData = DB::table('training_competencies')->exists()
            || DB::table('training_need_items')->exists()
            || DB::table('training_sessions')->exists();

        if (! $hasData && (bool) $this->option('allow-empty')) {
            $payload = [
                'summary' => [
                    'failed_probes' => 0,
                    'over_budget_probes' => 0,
                    'passed_probes' => 0,
                    'skipped_probes' => 0,
                    'skipped' => true,
                    'reason' => 'training_needs_dataset_empty',
                ],
                'results' => [],
            ];

            $this->outputPayload($payload);

            return self::SUCCESS;
        }

        $user = $this->resolveObserverUser();
        if (! $user) {
            $this->error('No user with Training Needs view access was found for render benchmarking.');

            return self::FAILURE;
        }

        $temporarySessionProvisioned = false;
        $sessionId = (int) (TrainingSession::query()->value('id') ?? 0);
        if ($sessionId === 0) {
            $sessionId = $this->provisionBenchmarkSessionId();
            $temporarySessionProvisioned = $sessionId > 0;
        }

        $budgets = [
            'planning_render' => $this->budgetPair('planning_render', 'planning'),
            'calendar_render' => $this->budgetPair('calendar_render', 'calendar'),
            'analytics_render' => $this->budgetPair('analytics_render', 'analytics'),
            'results_summary_render' => $this->budgetPair('results_summary_render', 'results_summary'),
            'reports_render' => $this->budgetPair('reports_render', 'reports'),
            'session_detail_workspace_render' => $this->budgetPair('session_detail_workspace_render', 'session_detail'),
            'calendar_session_detail_update' => $this->budgetPair('calendar_session_detail_update', 'calendar_select'),
        ];

        try {
            $results = [];
            $results[] = $this->probe('planning_render', $budgets['planning_render'], fn () => $profiler->measureRender($user, Dashboard::class, queryParams: ['tab' => 'planning']));
            $results[] = $this->probe('calendar_render', $budgets['calendar_render'], fn () => $profiler->measureRender($user, Dashboard::class, queryParams: ['tab' => 'calendar']));
            $results[] = $this->probe('analytics_render', $budgets['analytics_render'], fn () => $profiler->measureRender($user, Analytics::class));
            $results[] = $this->probe('results_summary_render', $budgets['results_summary_render'], fn () => $profiler->measureRender($user, ResultsSummary::class));
            $results[] = $this->probe('reports_render', $budgets['reports_render'], fn () => $profiler->measureRender($user, Reports::class));
            $results[] = $sessionId > 0
                ? $this->probe('session_detail_workspace_render', $budgets['session_detail_workspace_render'], fn () => $profiler->measureRender($user, SessionDetailWorkspace::class, ['sessionId' => $sessionId]))
                : $this->skipped('session_detail_workspace_render', 'training_session_missing');
            $results[] = $sessionId > 0
                ? $this->probe('calendar_session_detail_update', $budgets['calendar_session_detail_update'], fn () => $profiler->measureInteraction(
                    $user,
                    Dashboard::class,
                    fn ($test) => $test->call('selectSessionDetail', $sessionId),
                    queryParams: ['tab' => 'calendar'],
                ))
                : $this->skipped('calendar_session_detail_update', 'training_session_missing');
        } finally {
            if ($temporarySessionProvisioned && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }

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
        foreach (['training_annual_plans', 'training_sessions', 'training_feedback_forms'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function resolveObserverUser(): ?User
    {
        return User::query()
            ->orderBy('id')
            ->cursor()
            ->first(fn (User $user): bool => $user->canAny([
                'show-training-needs',
                'manage-training-needs',
                'review-training-needs',
                'export-training-needs',
            ]));
    }

    private function provisionBenchmarkSessionId(): int
    {
        DB::beginTransaction();

        $program = TrainingProgram::query()->create([
            'title' => '__benchmark_program',
            'slug' => '__benchmark-program',
            'delivery_type' => 'internal',
            'duration_hours' => 4,
            'is_active' => true,
        ]);

        $plan = TrainingAnnualPlan::query()->create([
            'title' => '__benchmark_plan',
            'plan_year' => (int) now()->year,
            'status' => 'draft',
        ]);

        $session = TrainingSession::query()->create([
            'training_annual_plan_id' => $plan->id,
            'training_program_id' => $program->id,
            'title' => '__benchmark_session',
            'scheduled_start_at' => now()->addWeek()->startOfDay()->setHour(10),
            'scheduled_end_at' => now()->addWeek()->startOfDay()->setHour(12),
            'status' => 'scheduled',
            'auto_fill_participants' => false,
        ]);

        TrainingFeedbackForm::query()->create([
            'training_session_id' => $session->id,
            'title' => '__benchmark_feedback',
            'status' => 'open',
            'questions' => [
                ['type' => 'rating', 'text' => 'Benchmark question'],
            ],
        ]);

        return (int) $session->id;
    }

    /**
     * @return array<string, float|int|string|null>
     */
    private function probe(string $flow, array $budget, callable $callback): array
    {
        try {
            $metrics = $callback();
            $renderMs = (float) data_get($metrics, 'render_ms', 0);
            $responseBytes = (int) data_get($metrics, 'response_bytes', 0);
            $exceeded = [];

            if ($responseBytes > (int) $budget['response_bytes']) {
                $exceeded[] = 'response_bytes';
            }

            if ($renderMs > (float) $budget['render_ms']) {
                $exceeded[] = 'render_ms';
            }

            return [
                'flow' => $flow,
                'status' => 'ok',
                'render_ms' => $renderMs,
                'response_bytes' => $responseBytes,
                'html_bytes' => data_get($metrics, 'html_bytes'),
                'snapshot_bytes' => data_get($metrics, 'snapshot_bytes'),
                'effects_bytes' => data_get($metrics, 'effects_bytes'),
                'budget' => $budget,
                'over_budget' => $exceeded !== [],
                'exceeded' => $exceeded,
                'error' => null,
            ];
        } catch (Throwable $throwable) {
            return [
                'flow' => $flow,
                'status' => 'failed',
                'render_ms' => null,
                'response_bytes' => null,
                'html_bytes' => null,
                'snapshot_bytes' => null,
                'effects_bytes' => null,
                'budget' => $budget,
                'over_budget' => false,
                'exceeded' => [],
                'error' => $throwable->getMessage(),
            ];
        }
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
        $responseBudget = (int) ($this->option($optionPrefix.'-response-budget') ?: config("training_needs.performance.render_budget.$flow.response_bytes", 200000));
        $renderBudget = (float) ($this->option($optionPrefix.'-render-budget') ?: config("training_needs.performance.render_budget.$flow.render_ms", 120));

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
