<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Models\User;
use App\Modules\Personnel\Livewire\AddPersonnel;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

class PersonnelCrudQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'personnel:crud-query-budget
        {--render-budget= : Max query count for initial CRUD renders}
        {--step-budget= : Max query count for step switching interactions}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Personnel CRUD flows';

    public function handle(PersonnelCrudBenchmarkFixtureService $fixtures): int
    {
        $user = $this->resolveBenchmarkUser();

        if (! $user) {
            $this->error('No user with Personnel CRUD permissions was found for query budgeting.');

            return self::FAILURE;
        }

        $personnel = $fixtures->ensureEditablePersonnel($user);
        $renderBudget = max(1, (int) ($this->option('render-budget') ?: config('personnel.performance.query_budget.personnel_crud_render', 48)));
        $stepBudget = max(1, (int) ($this->option('step-budget') ?: config('personnel.performance.query_budget.personnel_crud_step_change', 18)));

        $results = [
            $this->probe('add_personnel_render', $renderBudget, function () use ($user): void {
                Livewire::actingAs($user);
                Livewire::test(AddPersonnel::class);
            }),
            $this->probe('edit_personnel_render', $renderBudget, function () use ($user, $personnel): void {
                Livewire::actingAs($user);
                Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()]);
            }),
        ];

        Livewire::actingAs($user);
        $addComponent = Livewire::test(AddPersonnel::class);
        Livewire::actingAs($user);
        $editComponent = Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()]);

        foreach (range(2, 8) as $step) {
            $results[] = $this->probe("add_personnel_step_{$step}_select", $stepBudget, function () use ($user, $addComponent, $step): void {
                Livewire::actingAs($user);
                $addComponent->call('selectStep', $step);
            });

            $results[] = $this->probe("edit_personnel_step_{$step}_select", $stepBudget, function () use ($user, $editComponent, $step): void {
                Livewire::actingAs($user);
                $editComponent->call('selectStep', $step);
            });
        }

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

    private function resolveBenchmarkUser(): ?User
    {
        foreach (['add-personnels', 'edit-personnels'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        return User::query()
            ->orderBy('id')
            ->cursor()
            ->first(function (User $user): bool {
                return $user->can('add-personnels') && ($user->can('edit-personnels') || $user->can('update-personnels'));
            });
    }
}
