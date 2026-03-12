<?php

namespace App\Modules\Orders\Console\Commands;

use App\Models\User;
use App\Modules\Orders\Livewire\AllOrders;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Console\Command;
use Throwable;

class OrdersListRenderBenchmarkCommand extends Command
{
    protected $signature = 'orders:list-render-benchmark
        {--render-response-budget= : Max response size for orders render}
        {--render-ms-budget= : Max render time in ms for orders render}
        {--filter-response-budget= : Max response size for orders filter update}
        {--filter-ms-budget= : Max render time in ms for orders filter update}
        {--modal-response-budget= : Max response size for add order modal open}
        {--modal-ms-budget= : Max render time in ms for add order modal open}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Orders list flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        $user = $this->resolveUserForPermissions('show-orders');

        if (! $user) {
            $this->error('No user with Orders view permission was found for render benchmarking.');

            return self::FAILURE;
        }

        $modalUser = $this->resolveUserForPermissions('show-orders', 'add-orders') ?? $user;

        $budgets = [
            'orders_render' => [
                'response_bytes' => max(1, (int) ($this->option('render-response-budget') ?: config('orders.observability.list_render_budget.orders_render.response_bytes', 180000))),
                'render_ms' => max(1, (float) ($this->option('render-ms-budget') ?: config('orders.observability.list_render_budget.orders_render.render_ms', 180))),
            ],
            'orders_filter_update' => [
                'response_bytes' => max(1, (int) ($this->option('filter-response-budget') ?: config('orders.observability.list_render_budget.orders_filter_update.response_bytes', 180000))),
                'render_ms' => max(1, (float) ($this->option('filter-ms-budget') ?: config('orders.observability.list_render_budget.orders_filter_update.render_ms', 180))),
            ],
            'orders_add_modal_open' => [
                'response_bytes' => max(1, (int) ($this->option('modal-response-budget') ?: config('orders.observability.list_render_budget.orders_add_modal_open.response_bytes', 120000))),
                'render_ms' => max(1, (float) ($this->option('modal-ms-budget') ?: config('orders.observability.list_render_budget.orders_add_modal_open.render_ms', 120))),
            ],
        ];

        $results = [];
        $results[] = $this->probe('orders_render', $budgets['orders_render'], fn () => $profiler->measureRender($user, AllOrders::class));
        $results[] = $this->probe('orders_filter_update', $budgets['orders_filter_update'], fn () => $profiler->measureInteraction($user, AllOrders::class, fn ($component) => $component->call('setStatus', 'all')->set('search.order_no', '0908')));
        $results[] = $this->probe('orders_add_modal_open', $budgets['orders_add_modal_open'], fn () => $profiler->measureInteraction($modalUser, AllOrders::class, fn ($component) => $component->call('openSideMenu', 'add-order')));

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

    private function resolveUserForPermissions(string ...$permissions): ?User
    {
        return User::query()
            ->orderBy('id')
            ->cursor()
            ->first(fn (User $user): bool => collect($permissions)->every(fn (string $permission) => $user->can($permission)));
    }
}
