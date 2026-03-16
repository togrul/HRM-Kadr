<?php

namespace App\Modules\Staff\Console\Commands;

use App\Models\User;
use App\Modules\Staff\Livewire\Staffs;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Console\Command;
use Throwable;

class StaffListRenderBenchmarkCommand extends Command
{
    protected $signature = 'staff:list-render-benchmark
        {--render-response-budget= : Max response size for staff list render}
        {--render-ms-budget= : Max render time in ms for staff list render}
        {--vacancies-response-budget= : Max response size for vacancies view render}
        {--vacancies-ms-budget= : Max render time in ms for vacancies view render}
        {--modal-response-budget= : Max response size for add staff modal open}
        {--modal-ms-budget= : Max render time in ms for add staff modal open}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Staff list flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        $user = $this->resolveUserForPermissions('show-staff');

        if (! $user) {
            $this->error('No user with Staff view permission was found for render benchmarking.');

            return self::FAILURE;
        }

        $modalUser = $this->resolveUserForPermissions('show-staff', 'add-staff') ?? $user;

        $budgets = [
            'staffs_render' => [
                'response_bytes' => max(1, (int) ($this->option('render-response-budget') ?: config('staff.performance.render_budget.staffs_render.response_bytes', 260000))),
                'render_ms' => max(1, (float) ($this->option('render-ms-budget') ?: config('staff.performance.render_budget.staffs_render.render_ms', 220))),
            ],
            'staffs_vacancies_render' => [
                'response_bytes' => max(1, (int) ($this->option('vacancies-response-budget') ?: config('staff.performance.render_budget.staffs_vacancies_render.response_bytes', 220000))),
                'render_ms' => max(1, (float) ($this->option('vacancies-ms-budget') ?: config('staff.performance.render_budget.staffs_vacancies_render.render_ms', 220))),
            ],
            'staffs_add_modal_open' => [
                'response_bytes' => max(1, (int) ($this->option('modal-response-budget') ?: config('staff.performance.render_budget.staffs_add_modal_open.response_bytes', 180000))),
                'render_ms' => max(1, (float) ($this->option('modal-ms-budget') ?: config('staff.performance.render_budget.staffs_add_modal_open.render_ms', 160))),
            ],
        ];

        $results = [];
        $results[] = $this->probe('staffs_render', $budgets['staffs_render'], fn () => $profiler->measureRender($user, Staffs::class));
        $results[] = $this->probe('staffs_vacancies_render', $budgets['staffs_vacancies_render'], fn () => $profiler->measureInteraction($user, Staffs::class, fn ($component) => $component->call('showPage', 'vacancies')));
        $results[] = $this->probe('staffs_add_modal_open', $budgets['staffs_add_modal_open'], fn () => $profiler->measureInteraction($modalUser, Staffs::class, fn ($component) => $component->call('openSideMenu', 'add-staff')));

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
