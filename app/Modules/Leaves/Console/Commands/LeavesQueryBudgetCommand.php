<?php

namespace App\Modules\Leaves\Console\Commands;

use App\Models\User;
use App\Modules\Leaves\Livewire\Leaves;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Throwable;

class LeavesQueryBudgetCommand extends Command
{
    protected $signature = 'leaves:query-budget
        {--render-budget= : Max query count for leaves list render}
        {--status-budget= : Max query count for leaves status update}
        {--modal-budget= : Max query count for add leave modal open}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Leaves list flows';

    public function handle(): int
    {
        $user = $this->resolveUserForPermissions('show-leaves');

        if (! $user) {
            $this->error('No user with Leaves view permission was found for query budgeting.');

            return self::FAILURE;
        }

        $modalUser = $this->resolveUserForPermissions('show-leaves', 'add-leaves') ?? $user;

        $budgets = [
            'leaves_render' => max(1, (int) ($this->option('render-budget') ?: config('leaves.performance.query_budget.leaves_render', 14))),
            'leaves_status_update' => max(1, (int) ($this->option('status-budget') ?: config('leaves.performance.query_budget.leaves_status_update', 16))),
            'leaves_add_modal_open' => max(1, (int) ($this->option('modal-budget') ?: config('leaves.performance.query_budget.leaves_add_modal_open', 8))),
        ];

        $results = [];
        $results[] = $this->probe('leaves_render', $budgets['leaves_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(Leaves::class);
        });
        $results[] = $this->probe('leaves_status_update', $budgets['leaves_status_update'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(Leaves::class)
                ->call('setStatus', 'deleted');
        });
        $results[] = $this->probe('leaves_add_modal_open', $budgets['leaves_add_modal_open'], function () use ($modalUser): void {
            Livewire::actingAs($modalUser);
            Livewire::test(Leaves::class)
                ->call('openSideMenu', 'add-leave');
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

        return [
            'flow' => $flow,
            'status' => $status,
            'queries' => count($queries),
            'budget' => $budget,
            'over_budget' => count($queries) > $budget,
            'elapsed_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'db_time_ms' => round((float) collect($queries)->sum(fn ($query) => (float) ($query['time'] ?? 0)), 2),
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
