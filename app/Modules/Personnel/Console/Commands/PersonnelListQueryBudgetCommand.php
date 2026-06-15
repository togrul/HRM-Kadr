<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Models\User;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Livewire\TablePanel;
use Livewire\Livewire;

class PersonnelListQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'personnel:list-query-budget
        {--render-budget= : Max query count for personnel list render}
        {--initial-page-budget= : Max query count for full initial personnel page bootstrap}
        {--table-render-budget= : Max query count for personnel table render}
        {--status-budget= : Max query count for personnel status update}
        {--table-status-budget= : Max query count for personnel table status render}
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
            'all_personnel_initial_page' => max(1, (int) ($this->option('initial-page-budget') ?: config('personnel.performance.query_budget.all_personnel_initial_page', 32))),
            'personnel_table_render' => max(1, (int) ($this->option('table-render-budget') ?: config('personnel.performance.query_budget.personnel_table_render', 18))),
            'all_personnel_status_update' => max(1, (int) ($this->option('status-budget') ?: config('personnel.performance.query_budget.all_personnel_status_update', 24))),
            'personnel_table_status_render' => max(1, (int) ($this->option('table-status-budget') ?: config('personnel.performance.query_budget.personnel_table_status_render', 24))),
            'all_personnel_filter_open' => max(1, (int) ($this->option('filter-budget') ?: config('personnel.performance.query_budget.all_personnel_filter_open', 8))),
        ];

        $results = [];
        $results[] = $this->probe('all_personnel_render', $budgets['all_personnel_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AllPersonnel::class);
        });
        $results[] = $this->probe('all_personnel_initial_page', $budgets['all_personnel_initial_page'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AllPersonnel::class);
            Livewire::test(TablePanel::class, ['status' => 'current']);
        });
        $results[] = $this->probe('personnel_table_render', $budgets['personnel_table_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(TablePanel::class);
        });
        $results[] = $this->probe('all_personnel_status_update', $budgets['all_personnel_status_update'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AllPersonnel::class)
                ->call('setStatus', 'all');
        });
        $results[] = $this->probe('personnel_table_status_render', $budgets['personnel_table_status_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(TablePanel::class, ['status' => 'all']);
        });
        $results[] = $this->probe('all_personnel_filter_open', $budgets['all_personnel_filter_open'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(AllPersonnel::class)
                ->call('openFilter');
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
