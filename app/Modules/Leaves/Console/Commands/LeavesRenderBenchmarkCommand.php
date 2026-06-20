<?php

namespace App\Modules\Leaves\Console\Commands;

use App\Console\Support\AbstractRenderBenchmarkCommand;
use App\Modules\Leaves\Livewire\Leaves;
use App\Support\Livewire\LivewireComponentProfiler;

class LeavesRenderBenchmarkCommand extends AbstractRenderBenchmarkCommand
{
    protected $signature = 'leaves:render-benchmark
        {--render-response-budget= : Max response size for leaves list render}
        {--render-ms-budget= : Max render time in ms for leaves list render}
        {--status-response-budget= : Max response size for leaves status update}
        {--status-ms-budget= : Max render time in ms for leaves status update}
        {--modal-response-budget= : Max response size for add leave modal open}
        {--modal-ms-budget= : Max render time in ms for add leave modal open}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Leaves list flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        $user = $this->resolveUserForPermissions('show-leaves');

        if (! $user) {
            $this->error('No user with Leaves view permission was found for render benchmarking.');

            return self::FAILURE;
        }

        $modalUser = $this->resolveUserForPermissions('show-leaves', 'add-leaves') ?? $user;

        $budgets = [
            'leaves_render' => [
                'response_bytes' => max(1, (int) ($this->option('render-response-budget') ?: config('leaves.performance.render_budget.leaves_render.response_bytes', 260000))),
                'render_ms' => max(1, (float) ($this->option('render-ms-budget') ?: config('leaves.performance.render_budget.leaves_render.render_ms', 220))),
            ],
            'leaves_status_update' => [
                'response_bytes' => max(1, (int) ($this->option('status-response-budget') ?: config('leaves.performance.render_budget.leaves_status_update.response_bytes', 220000))),
                'render_ms' => max(1, (float) ($this->option('status-ms-budget') ?: config('leaves.performance.render_budget.leaves_status_update.render_ms', 180))),
            ],
            'leaves_add_modal_open' => [
                'response_bytes' => max(1, (int) ($this->option('modal-response-budget') ?: config('leaves.performance.render_budget.leaves_add_modal_open.response_bytes', 180000))),
                'render_ms' => max(1, (float) ($this->option('modal-ms-budget') ?: config('leaves.performance.render_budget.leaves_add_modal_open.render_ms', 160))),
            ],
        ];

        $results = [];
        $results[] = $this->probe('leaves_render', $budgets['leaves_render'], function () use ($profiler, $user) {
            // Warm Blade/Livewire compilation so the benchmark reflects steady-state render cost.
            $profiler->measureRender($user, Leaves::class);

            return $profiler->measureRender($user, Leaves::class);
        });
        $results[] = $this->probe('leaves_status_update', $budgets['leaves_status_update'], fn () => $profiler->measureInteraction($user, Leaves::class, fn ($component) => $component->call('setStatus', 'deleted')));
        $results[] = $this->probe('leaves_add_modal_open', $budgets['leaves_add_modal_open'], fn () => $profiler->measureInteraction($modalUser, Leaves::class, fn ($component) => $component->call('openAddLeaveModal')));

        return $this->finalize($results);
    }
}
