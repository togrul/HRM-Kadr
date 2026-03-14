<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Models\User;
use App\Modules\Personnel\Livewire\AddPersonnel;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Throwable;

class PersonnelCrudRenderBenchmarkCommand extends Command
{
    protected $signature = 'personnel:crud-render-benchmark
        {--render-response-budget= : Max response size for initial CRUD renders}
        {--render-ms-budget= : Max render time for initial CRUD renders}
        {--step-response-budget= : Max response size for step switching interactions}
        {--step-ms-budget= : Max render time for step switching interactions}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time, payload size and memory for Personnel CRUD flows';

    public function handle(
        LivewireComponentProfiler $profiler,
        PersonnelCrudBenchmarkFixtureService $fixtures
    ): int {
        $user = $this->resolveBenchmarkUser();

        if (! $user) {
            $this->error('No user with Personnel CRUD permissions was found for render benchmarking.');

            return self::FAILURE;
        }

        $personnel = $fixtures->ensureEditablePersonnel($user);
        $renderBudget = [
            'response_bytes' => max(1, (int) ($this->option('render-response-budget') ?: config('personnel.performance.render_budget.personnel_crud_render.response_bytes', 320000))),
            'render_ms' => max(1, (float) ($this->option('render-ms-budget') ?: config('personnel.performance.render_budget.personnel_crud_render.render_ms', 800))),
        ];
        $stepBudget = [
            'response_bytes' => max(1, (int) ($this->option('step-response-budget') ?: config('personnel.performance.render_budget.personnel_crud_step_change.response_bytes', 320000))),
            'render_ms' => max(1, (float) ($this->option('step-ms-budget') ?: config('personnel.performance.render_budget.personnel_crud_step_change.render_ms', 600))),
        ];

        $results = [
            $this->probe('add_personnel_render', $renderBudget, fn () => $profiler->measureRender($user, AddPersonnel::class)),
            $this->probe('edit_personnel_render', $renderBudget, fn () => $profiler->measureRender($user, EditPersonnel::class, ['personnelModel' => $personnel->getKey()])),
        ];

        foreach (range(2, 8) as $step) {
            $results[] = $this->probe(
                "add_personnel_step_{$step}_select",
                $stepBudget,
                fn () => $profiler->measureInteraction($user, AddPersonnel::class, fn ($component) => $component->call('selectStep', $step))
            );

            $results[] = $this->probe(
                "edit_personnel_step_{$step}_select",
                $stepBudget,
                fn () => $profiler->measureInteraction(
                    $user,
                    EditPersonnel::class,
                    fn ($component) => $component->call('selectStep', $step),
                    ['personnelModel' => $personnel->getKey()]
                )
            );
        }

        return $this->finalize($results);
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
                'memory_bytes' => data_get($metrics, 'memory_bytes'),
                'peak_memory_bytes' => data_get($metrics, 'peak_memory_bytes'),
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
                'memory_bytes' => null,
                'peak_memory_bytes' => null,
                'budget' => $budget,
                'over_budget' => false,
                'exceeded' => [],
                'error' => $throwable->getMessage(),
            ];
        }
    }

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
                ['flow', 'status', 'render_ms', 'response_bytes', 'memory_bytes', 'peak_memory_bytes', 'budget_response', 'budget_render_ms', 'over_budget', 'error'],
                collect($results)->map(fn (array $result) => [
                    $result['flow'],
                    $result['status'],
                    $result['render_ms'],
                    $result['response_bytes'],
                    $result['memory_bytes'],
                    $result['peak_memory_bytes'],
                    data_get($result, 'budget.response_bytes'),
                    data_get($result, 'budget.render_ms'),
                    $result['over_budget'] ? implode(',', $result['exceeded']) : 'no',
                    $result['error'] ?? '-',
                ])->all()
            );
        }

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0) ? self::SUCCESS : self::FAILURE;
    }
}
