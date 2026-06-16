<?php

namespace App\Modules\Orders\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Modules\Orders\Livewire\AllOrders;
use Livewire\Livewire;

class OrdersListQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'orders:list-query-budget
        {--render-budget= : Max query count for orders render}
        {--filter-budget= : Max query count for orders filter update}
        {--modal-budget= : Max query count for add order modal shell open}
        {--modal-panel-budget= : Max query count for add order panel render}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Orders list flows';

    public function handle(): int
    {
        $user = $this->resolveUserForPermissions('show-orders');

        if (! $user) {
            $this->error('No user with Orders view permission was found for query budgeting.');

            return self::FAILURE;
        }

        $budgets = [
            'orders_render' => max(1, (int) ($this->option('render-budget') ?: config('orders.observability.list_query_budget.orders_render', 14))),
            'orders_filter_update' => max(1, (int) ($this->option('filter-budget') ?: config('orders.observability.list_query_budget.orders_filter_update', 28))),
        ];

        $results = [];
        $results[] = $this->probe('orders_render', $budgets['orders_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AllOrders::class);
        });
        $results[] = $this->probe('orders_filter_update', $budgets['orders_filter_update'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AllOrders::class)
                ->call('setStatus', 'all')
                ->set('search.order_no', '0908');
        });

        $summary = [
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
        ];

        $payload = ['summary' => $summary, 'results' => $results];

        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['flow', 'status', 'queries', 'budget', 'over_budget', 'elapsed_ms', 'db_time_ms', 'error'],
                collect($results)->map(fn (array $result) => [
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

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0) ? self::SUCCESS : self::FAILURE;
    }
}
