<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Console\Support\AbstractRenderBenchmarkCommand;
use App\Models\User;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Livewire\TablePanel;
use App\Support\Livewire\LivewireComponentProfiler;

class PersonnelListRenderBenchmarkCommand extends AbstractRenderBenchmarkCommand
{
    protected $signature = 'personnel:list-render-benchmark
        {--render-response-budget= : Max response size for personnel list render}
        {--render-ms-budget= : Max render time in ms for personnel list render}
        {--initial-page-response-budget= : Max response size for full initial personnel page bootstrap}
        {--initial-page-ms-budget= : Max render time in ms for full initial personnel page bootstrap}
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
            'all_personnel_initial_page' => [
                'response_bytes' => max(1, (int) ($this->option('initial-page-response-budget') ?: config('personnel.performance.render_budget.all_personnel_initial_page.response_bytes', 420000))),
                'render_ms' => max(1, (float) ($this->option('initial-page-ms-budget') ?: config('personnel.performance.render_budget.all_personnel_initial_page.render_ms', 800))),
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
        $results[] = $this->probe('all_personnel_initial_page', $budgets['all_personnel_initial_page'], fn () => $this->mergeMetrics(
            $profiler->measureRender($user, AllPersonnel::class),
            $profiler->measureRender($user, TablePanel::class, ['status' => 'current'])
        ));
        $results[] = $this->probe('personnel_table_render', $budgets['personnel_table_render'], fn () => $profiler->measureRender($user, TablePanel::class));
        $results[] = $this->probe('all_personnel_status_update', $budgets['all_personnel_status_update'], fn () => $profiler->measureInteraction($user, AllPersonnel::class, fn ($component) => $component->call('setStatus', 'all')));
        $results[] = $this->probe('personnel_table_status_render', $budgets['personnel_table_status_render'], fn () => $profiler->measureRender($user, TablePanel::class, ['status' => 'all']));
        $results[] = $this->probe('all_personnel_filter_open', $budgets['all_personnel_filter_open'], fn () => $profiler->measureInteraction($user, AllPersonnel::class, fn ($component) => $component->call('openFilter')));

        return $this->finalize($results);
    }

    /**
     * @param  array<string, float|int|string|null>  ...$metrics
     * @return array<string, float|int|string|null>
     */
    private function mergeMetrics(array ...$metrics): array
    {
        $sum = static fn (string $key): float|int => array_reduce(
            $metrics,
            fn ($carry, $item) => $carry + (float) data_get($item, $key, 0),
            0
        );

        return [
            'render_ms' => round((float) $sum('render_ms'), 2),
            'response_bytes' => (int) $sum('response_bytes'),
            'html_bytes' => (int) $sum('html_bytes'),
            'snapshot_bytes' => (int) $sum('snapshot_bytes'),
            'effects_bytes' => (int) $sum('effects_bytes'),
        ];
    }
}
