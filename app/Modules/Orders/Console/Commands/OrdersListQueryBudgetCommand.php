<?php

namespace App\Modules\Orders\Console\Commands;

use App\Models\User;
use App\Modules\Orders\Livewire\AllOrders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Throwable;

class OrdersListQueryBudgetCommand extends Command
{
    protected $signature = 'orders:list-query-budget
        {--render-budget= : Max query count for orders render}
        {--filter-budget= : Max query count for orders filter update}
        {--modal-budget= : Max query count for add order modal open}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Orders list flows';

    public function handle(): int
    {
        $user = $this->resolveUserForPermissions('show-orders');

        if (! $user) {
            $this->error('No user with Orders view permission was found for query budgeting.');

            return self::FAILURE;
        }

        $modalUser = $this->resolveUserForPermissions('show-orders', 'add-orders') ?? $user;

        $budgets = [
            'orders_render' => max(1, (int) ($this->option('render-budget') ?: config('orders.observability.list_query_budget.orders_render', 14))),
            'orders_filter_update' => max(1, (int) ($this->option('filter-budget') ?: config('orders.observability.list_query_budget.orders_filter_update', 28))),
            'orders_add_modal_open' => max(1, (int) ($this->option('modal-budget') ?: config('orders.observability.list_query_budget.orders_add_modal_open', 4))),
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
        $results[] = $this->probe('orders_add_modal_open', $budgets['orders_add_modal_open'], function () use ($modalUser): void {
            Livewire::actingAs($modalUser);
            Livewire::test(AllOrders::class)
                ->call('openSideMenu', 'add-order');
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

    private function resolveUserForPermissions(string ...$permissions): ?User
    {
        return User::query()
            ->orderBy('id')
            ->cursor()
            ->first(fn (User $user): bool => collect($permissions)->every(fn (string $permission) => $user->can($permission)));
    }
}
