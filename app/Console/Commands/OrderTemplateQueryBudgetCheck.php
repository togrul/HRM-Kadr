<?php

namespace App\Console\Commands;

use App\Models\OrderLog;
use App\Models\OrderType;
use App\Services\Orders\OrderPrintPayloadFactory;
use App\Services\Orders\OrderTemplateFormSchemaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderTemplateQueryBudgetCheck extends Command
{
    protected $signature = 'orders:templates:query-budget
        {--order-type= : order_type_id for add-flow schema probe}
        {--order-no= : order_no for edit/print probes}
        {--allow-empty : Return success when no reference order exists (useful for empty CI DB)}
        {--add-budget= : max queries for add form schema flow}
        {--edit-budget= : max queries for edit order load flow}
        {--print-budget= : max queries for print payload build flow}
        {--json : Print report as JSON}';

    protected $description = 'Run query budget checks for Add/Edit/Print metadata flows';

    public function handle(
        OrderTemplateFormSchemaService $schemaService,
        OrderPrintPayloadFactory $payloadFactory
    ): int {
        $configuredBudgets = [
            'add_form_schema' => (int) config('orders.observability.query_budget.add_form_schema', 15),
            'edit_order_load' => (int) config('orders.observability.query_budget.edit_order_load', 15),
            'print_payload_build' => (int) config('orders.observability.query_budget.print_payload_build', 20),
        ];
        $budgets = [
            'add_form_schema' => max(1, (int) ($this->option('add-budget') ?: $configuredBudgets['add_form_schema'])),
            'edit_order_load' => max(1, (int) ($this->option('edit-budget') ?: $configuredBudgets['edit_order_load'])),
            'print_payload_build' => max(1, (int) ($this->option('print-budget') ?: $configuredBudgets['print_payload_build'])),
        ];

        $orderNo = trim((string) ($this->option('order-no') ?? ''));
        $orderTypeId = is_numeric($this->option('order-type')) ? (int) $this->option('order-type') : 0;

        $referenceOrder = $this->resolveReferenceOrder($orderNo, $orderTypeId);
        if (! $referenceOrder) {
            if ((bool) $this->option('allow-empty')) {
                $payload = [
                    'summary' => [
                        'order_type_id' => $orderTypeId > 0 ? $orderTypeId : null,
                        'order_no' => null,
                        'failed_probes' => 0,
                        'over_budget_probes' => 0,
                        'passed_probes' => 0,
                        'skipped' => true,
                        'reason' => 'reference_order_not_found',
                    ],
                    'results' => [],
                ];
                if ((bool) $this->option('json')) {
                    $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    $this->warn('Reference order was not found. Skipping probes because --allow-empty is enabled.');
                }

                return self::SUCCESS;
            }

            $this->error('Reference order was not found. Provide --order-no or ensure order logs exist for selected order type.');

            return self::FAILURE;
        }

        if ($orderTypeId <= 0) {
            $orderTypeId = (int) ($referenceOrder->order_type_id ?? 0);
        }

        if ($orderTypeId <= 0 || ! OrderType::query()->whereKey($orderTypeId)->exists()) {
            $this->error('order_type_id could not be resolved.');

            return self::FAILURE;
        }

        $results = [];
        $results[] = $this->probe('add_form_schema', $budgets['add_form_schema'], function () use ($schemaService, $orderTypeId): void {
            $schemaService->resolveForOrderType($orderTypeId);
        });

        $results[] = $this->probe('edit_order_load', $budgets['edit_order_load'], function () use ($referenceOrder): void {
            OrderLog::query()
                ->with(['order', 'components', 'personnels', 'status', 'attributes'])
                ->where('order_no', (string) $referenceOrder->order_no)
                ->first();
        });

        $results[] = $this->probe('print_payload_build', $budgets['print_payload_build'], function () use ($payloadFactory, $referenceOrder): void {
            $order = OrderLog::query()->find((int) $referenceOrder->id);
            if (! $order) {
                return;
            }
            $payloadFactory->build($order);
        });

        $summary = [
            'order_type_id' => $orderTypeId,
            'order_no' => (string) $referenceOrder->order_no,
            'failed_probes' => collect($results)->where('status', 'failed')->count(),
            'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
            'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'results' => $results,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0)
                ? self::SUCCESS
                : self::FAILURE;
        }

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

        $this->newLine();
        $this->table(
            ['metric', 'value'],
            collect($summary)->map(fn ($value, $key) => [$key, (string) $value])->values()->all()
        );

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function resolveReferenceOrder(string $orderNo, int $orderTypeId): ?OrderLog
    {
        if ($orderNo !== '') {
            return OrderLog::query()->where('order_no', $orderNo)->first();
        }

        return OrderLog::query()
            ->when($orderTypeId > 0, fn ($query) => $query->where('order_type_id', $orderTypeId))
            ->latest('id')
            ->first();
    }

    /**
     * @return array{
     *   flow:string,
     *   status:string,
     *   queries:int,
     *   budget:int,
     *   over_budget:bool,
     *   elapsed_ms:float,
     *   db_time_ms:float,
     *   error:?string
     * }
     */
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
}
