<?php

namespace App\Modules\Staff\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Modules\Staff\Livewire\Staffs;
use Livewire\Livewire;

class StaffListQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'staff:list-query-budget
        {--render-budget= : Max query count for staff list render}
        {--vacancies-budget= : Max query count for vacancies view render}
        {--modal-budget= : Max query count for add staff modal open}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Staff list flows';

    public function handle(): int
    {
        $user = $this->resolveUserForPermissions('show-staff');

        if (! $user) {
            $this->error('No user with Staff view permission was found for query budgeting.');

            return self::FAILURE;
        }

        $modalUser = $this->resolveUserForPermissions('show-staff', 'add-staff') ?? $user;

        $budgets = [
            'staffs_render' => max(1, (int) ($this->option('render-budget') ?: config('staff.performance.query_budget.staffs_render', 12))),
            'staffs_vacancies_render' => max(1, (int) ($this->option('vacancies-budget') ?: config('staff.performance.query_budget.staffs_vacancies_render', 12))),
            'staffs_add_modal_open' => max(1, (int) ($this->option('modal-budget') ?: config('staff.performance.query_budget.staffs_add_modal_open', 8))),
        ];

        $results = [];
        $results[] = $this->probe('staffs_render', $budgets['staffs_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(Staffs::class);
        });
        $results[] = $this->probe('staffs_vacancies_render', $budgets['staffs_vacancies_render'], function () use ($user): void {
            Livewire::actingAs($user);
            Livewire::test(Staffs::class)
                ->call('showPage', 'vacancies');
        });
        $results[] = $this->probe('staffs_add_modal_open', $budgets['staffs_add_modal_open'], function () use ($modalUser): void {
            Livewire::actingAs($modalUser);
            Livewire::test(Staffs::class)
                ->call('openSideMenu', 'add-staff');
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
