<?php

namespace App\Modules\Reports\Console\Commands;

use App\Models\User;
use App\Modules\Reports\Livewire\Comparisons;
use App\Modules\Reports\Livewire\DynamicBuilder;
use App\Modules\Reports\Livewire\Overview;
use App\Modules\Reports\Livewire\StandardReports;
use App\Support\Livewire\LivewireComponentProfiler;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class ReportsRenderBenchmarkCommand extends Command
{
    protected $signature = 'reports:render-benchmark
        {--year= : Target year}
        {--month= : Target month}
        {--overview-response-budget= : Max response size for overview render}
        {--overview-render-budget= : Max render time in ms for overview render}
        {--standard-response-budget= : Max response size for standard reports render}
        {--standard-render-budget= : Max render time in ms for standard reports render}
        {--dynamic-response-budget= : Max response size for dynamic builder render}
        {--dynamic-render-budget= : Max render time in ms for dynamic builder render}
        {--comparisons-response-budget= : Max response size for comparisons render}
        {--comparisons-render-budget= : Max render time in ms for comparisons render}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Reports dashboard surfaces';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        $user = $this->resolveObserverUser();
        if (! $user) {
            $this->error('No user with Reports permissions was found for render benchmarking.');

            return self::FAILURE;
        }

        $now = Carbon::now();
        $year = is_numeric($this->option('year')) ? (int) $this->option('year') : (int) $now->year;
        $month = is_numeric($this->option('month')) ? (int) $this->option('month') : (int) $now->month;
        $query = ['year' => $year, 'month' => $month];

        $budgets = [
            'overview_render' => $this->budgetPair('overview_render', 'overview'),
            'standard_reports_render' => $this->budgetPair('standard_reports_render', 'standard'),
            'dynamic_builder_render' => $this->budgetPair('dynamic_builder_render', 'dynamic'),
            'comparisons_render' => $this->budgetPair('comparisons_render', 'comparisons'),
        ];

        $results = [];
        $results[] = $this->probe('overview_render', $budgets['overview_render'], fn () => $profiler->measureRender($user, Overview::class, [], $query));
        $results[] = $this->probe('standard_reports_render', $budgets['standard_reports_render'], fn () => $profiler->measureRender($user, StandardReports::class, [], $query));
        $results[] = $this->probe('dynamic_builder_render', $budgets['dynamic_builder_render'], fn () => $profiler->measureRender($user, DynamicBuilder::class, [], $query));
        $results[] = $this->probe('comparisons_render', $budgets['comparisons_render'], fn () => $profiler->measureRender($user, Comparisons::class, [], $query));

        $payload = [
            'summary' => [
                'failed_probes' => collect($results)->where('status', 'failed')->count(),
                'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
                'passed_probes' => collect($results)->where('status', 'ok')->count(),
            ],
            'results' => $results,
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['flow', 'status', 'render_ms', 'response_bytes', 'budget_response', 'budget_render_ms', 'over_budget', 'memory_bytes', 'peak_memory_bytes', 'error'],
                collect($results)->map(fn (array $result) => [
                    $result['flow'],
                    $result['status'],
                    $result['render_ms'],
                    $result['response_bytes'],
                    data_get($result, 'budget.response_bytes'),
                    data_get($result, 'budget.render_ms'),
                    $result['over_budget'] ? implode(',', $result['exceeded']) : 'no',
                    $result['memory_bytes'],
                    $result['peak_memory_bytes'],
                    $result['error'] ?? '-',
                ])->all()
            );
        }

        return ((int) data_get($payload, 'summary.failed_probes', 0) === 0 && (int) data_get($payload, 'summary.over_budget_probes', 0) === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function resolveObserverUser(): ?User
    {
        return User::query()
            ->orderBy('id')
            ->cursor()
            ->first(fn (User $user): bool => $user->canAny(['show-reports', 'export-reports']));
    }

    private function budgetPair(string $flow, string $optionPrefix): array
    {
        $responseBudget = (int) ($this->option($optionPrefix.'-response-budget') ?: config("reports.performance.render_budget.$flow.response_bytes", 180000));
        $renderBudget = (float) ($this->option($optionPrefix.'-render-budget') ?: config("reports.performance.render_budget.$flow.render_ms", 180));

        return [
            'response_bytes' => max(1, $responseBudget),
            'render_ms' => max(1, $renderBudget),
        ];
    }

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
                'memory_bytes' => (int) data_get($metrics, 'memory_bytes', 0),
                'peak_memory_bytes' => (int) data_get($metrics, 'peak_memory_bytes', 0),
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
                'memory_bytes' => null,
                'peak_memory_bytes' => null,
                'budget' => $budget,
                'over_budget' => false,
                'exceeded' => [],
                'error' => $throwable->getMessage(),
            ];
        }
    }
}
