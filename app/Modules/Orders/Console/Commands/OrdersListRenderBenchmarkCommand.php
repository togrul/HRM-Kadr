<?php

namespace App\Modules\Orders\Console\Commands;

use App\Console\Support\AbstractRenderBenchmarkCommand;
use App\Modules\Orders\Livewire\AllOrders;
use App\Support\Livewire\LivewireComponentProfiler;

class OrdersListRenderBenchmarkCommand extends AbstractRenderBenchmarkCommand
{
    protected $signature = 'orders:list-render-benchmark
        {--render-response-budget= : Max response size for orders render}
        {--render-ms-budget= : Max render time in ms for orders render}
        {--filter-response-budget= : Max response size for orders filter update}
        {--filter-ms-budget= : Max render time in ms for orders filter update}
        {--modal-response-budget= : Max response size for add order modal shell open}
        {--modal-ms-budget= : Max render time in ms for add order modal shell open}
        {--modal-panel-response-budget= : Max response size for add order panel render}
        {--modal-panel-ms-budget= : Max render time in ms for add order panel render}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Orders list flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        $user = $this->resolveUserForPermissions('show-orders');

        if (! $user) {
            $this->error('No user with Orders view permission was found for render benchmarking.');

            return self::FAILURE;
        }

        $budgets = [
            'orders_render' => [
                'response_bytes' => max(1, (int) ($this->option('render-response-budget') ?: config('orders.observability.list_render_budget.orders_render.response_bytes', 180000))),
                'render_ms' => max(1, (float) ($this->option('render-ms-budget') ?: config('orders.observability.list_render_budget.orders_render.render_ms', 180))),
            ],
            'orders_filter_update' => [
                'response_bytes' => max(1, (int) ($this->option('filter-response-budget') ?: config('orders.observability.list_render_budget.orders_filter_update.response_bytes', 180000))),
                'render_ms' => max(1, (float) ($this->option('filter-ms-budget') ?: config('orders.observability.list_render_budget.orders_filter_update.render_ms', 180))),
            ],
        ];

        $results = [];
        $results[] = $this->probe('orders_render', $budgets['orders_render'], fn () => $profiler->measureRender($user, AllOrders::class));
        $results[] = $this->probe('orders_filter_update', $budgets['orders_filter_update'], fn () => $profiler->measureInteraction($user, AllOrders::class, fn ($component) => $component->call('setStatus', 'all')->set('search.order_no', '0908')));

        return $this->finalize($results);
    }
}
