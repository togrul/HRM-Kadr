<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Models\User;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Livewire\TablePanel;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Console\Command;
use Throwable;

class PersonnelListRenderBenchmarkCommand extends Command
{
    protected $signature = 'personnel:list-render-benchmark
        {--render-response-budget= : Max response size for personnel list render}
        {--render-ms-budget= : Max render time in ms for personnel list render}
        {--table-render-response-budget= : Max response size for personnel table render}
        {--table-render-ms-budget= : Max render time in ms for personnel table render}
        {--status-response-budget= : Max response size for personnel status update}
        {--status-ms-budget= : Max render time in ms for personnel status update}
        {--table-status-response-budget= : Max response size for personnel table status render}
        {--table-status-ms-budget= : Max render time in ms for personnel table status render}
        {--filter-response-budget= : Max response size for personnel filter open}
        {--filter-ms-budget= : Max render time in ms for personnel filter open}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Personnel list flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        $user = User::query()
            ->orderBy('id')
            ->cursor()
            ->first(fn (User $user): bool => $user->can('show-personnels'));

        if (! $user) {
            $this->error('No user with Personnel view permission was found for render benchmarking.');

            return self::FAILURE;
        }

        $budgets = [
            'all_personnel_render' => [
                'response_bytes' => max(1, (int) ($this->option('render-response-budget') ?: config('personnel.performance.render_budget.all_personnel_render.response_bytes', 220000))),
                'render_ms' => max(1, (float) ($this->option('render-ms-budget') ?: config('personnel.performance.render_budget.all_personnel_render.render_ms', 220))),
            ],
            'personnel_table_render' => [
                'response_bytes' => max(1, (int) ($this->option('table-render-response-budget') ?: config('personnel.performance.render_budget.personnel_table_render.response_bytes', 180000))),
                'render_ms' => max(1, (float) ($this->option('table-render-ms-budget') ?: config('personnel.performance.render_budget.personnel_table_render.render_ms', 220))),
            ],
            'all_personnel_status_update' => [
                'response_bytes' => max(1, (int) ($this->option('status-response-budget') ?: config('personnel.performance.render_budget.all_personnel_status_update.response_bytes', 180000))),
                'render_ms' => max(1, (float) ($this->option('status-ms-budget') ?: config('personnel.performance.render_budget.all_personnel_status_update.render_ms', 180))),
            ],
            'personnel_table_status_render' => [
                'response_bytes' => max(1, (int) ($this->option('table-status-response-budget') ?: config('personnel.performance.render_budget.personnel_table_status_render.response_bytes', 190000))),
                'render_ms' => max(1, (float) ($this->option('table-status-ms-budget') ?: config('personnel.performance.render_budget.personnel_table_status_render.render_ms', 220))),
            ],
            'all_personnel_filter_open' => [
                'response_bytes' => max(1, (int) ($this->option('filter-response-budget') ?: config('personnel.performance.render_budget.all_personnel_filter_open.response_bytes', 190000))),
                'render_ms' => max(1, (float) ($this->option('filter-ms-budget') ?: config('personnel.performance.render_budget.all_personnel_filter_open.render_ms', 180))),
            ],
        ];

        $results = [];
        $results[] = $this->probe('all_personnel_render', $budgets['all_personnel_render'], fn () => $profiler->measureRender($user, AllPersonnel::class));
        $results[] = $this->probe('personnel_table_render', $budgets['personnel_table_render'], fn () => $profiler->measureRender($user, TablePanel::class));
        $results[] = $this->probe('all_personnel_status_update', $budgets['all_personnel_status_update'], fn () => $profiler->measureInteraction($user, AllPersonnel::class, fn ($component) => $component->call('setStatus', 'all')));
        $results[] = $this->probe('personnel_table_status_render', $budgets['personnel_table_status_render'], fn () => $profiler->measureRender($user, TablePanel::class, ['status' => 'all']));
        $results[] = $this->probe('all_personnel_filter_open', $budgets['all_personnel_filter_open'], fn () => $profiler->measureInteraction($user, AllPersonnel::class, fn ($component) => $component->call('openFilter')));

        return $this->finalize($results);
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
     * @param  array<int, array<string, mixed>>  $results
     */
    private function finalize(array $results): int
    {
        $summary = [
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->count(),
        ];

        $payload = ['summary' => $summary, 'results' => $results];

        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['flow', 'status', 'render_ms', 'response_bytes', 'budget_response', 'budget_render_ms', 'over_budget', 'html_bytes', 'snapshot_bytes', 'effects_bytes', 'error'],
                collect($results)->map(fn (array $result) => [
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

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0) ? self::SUCCESS : self::FAILURE;
    }
}
