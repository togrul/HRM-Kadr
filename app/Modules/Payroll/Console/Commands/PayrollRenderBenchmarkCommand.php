<?php

namespace App\Modules\Payroll\Console\Commands;

use App\Console\Support\AbstractRenderBenchmarkCommand;
use App\Modules\Payroll\Livewire\Dashboard;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Support\Facades\Schema;

class PayrollRenderBenchmarkCommand extends AbstractRenderBenchmarkCommand
{
    protected $signature = 'payroll:render-benchmark
        {--response-budget= : Max initial response size in bytes}
        {--render-budget= : Max render time in ms}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Payroll flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        if (! Schema::hasTable('payroll_runs')) {
            $this->error('Payroll tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $user = $this->resolveUserForPermissions('show-payroll');

        if (! $user) {
            $this->error('No user with Payroll view access was found.');

            return self::FAILURE;
        }

        $budget = [
            'response_bytes' => (int) ($this->option('response-budget') ?: 200000),
            'render_ms' => (float) ($this->option('render-budget') ?: 120),
        ];

        $result = $this->probe('runs_render', $budget, fn () => $profiler->measureRender($user, Dashboard::class, queryParams: ['tab' => 'runs']));

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $this->line(sprintf('runs_render: %s ms, %s bytes%s', $result['render_ms'], $result['response_bytes'], $result['over_budget'] ? ' OVER' : ''));
        }

        return ($result['status'] === 'ok' && ! $result['over_budget']) ? self::SUCCESS : self::FAILURE;
    }
}
