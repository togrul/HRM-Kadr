<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Models\User;
use App\Modules\Personnel\Livewire\AllPersonnel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Throwable;

class PersonnelListQueryBudgetCommand extends Command
{
    protected $signature = 'personnel:list-query-budget
        {--render-budget= : Max query count for personnel list render}
        {--status-budget= : Max query count for personnel status update}
        {--filter-budget= : Max query count for personnel filter-detail open}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Personnel list flows';

    public function handle(): int
    {
        $user = User::query()
            ->orderBy('id')
            ->cursor()
            ->first(fn (User $user): bool => $user->can('show-personnels'));

        if (! $user) {
            $this->error('No user with Personnel view permission was found for query budgeting.');

            return self::FAILURE;
        }

        $budgets = [
            'all_personnel_render' => max(1, (int) ($this->option('render-budget') ?: config('personnel.performance.query_budget.all_personnel_render', 18))),
            'all_personnel_status_update' => max(1, (int) ($this->option('status-budget') ?: config('personnel.performance.query_budget.all_personnel_status_update', 24))),
            'all_personnel_filter_open' => max(1, (int) ($this->option('filter-budget') ?: config('personnel.performance.query_budget.all_personnel_filter_open', 8))),
        ];

        $results = [];
        $results[] = $this->probe('all_personnel_render', $budgets['all_personnel_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AllPersonnel::class);
        });
        $results[] = $this->probe('all_personnel_status_update', $budgets['all_personnel_status_update'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AllPersonnel::class)
                ->call('setStatus', 'all');
        });
        $results[] = $this->probe('all_personnel_filter_open', $budgets['all_personnel_filter_open'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AllPersonnel::class)
                ->call('openFilter')
                ->call('handleFilterDetailReady');
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
}
