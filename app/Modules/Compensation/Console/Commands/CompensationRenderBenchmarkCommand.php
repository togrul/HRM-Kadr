<?php

namespace App\Modules\Compensation\Console\Commands;

use App\Console\Support\AbstractRenderBenchmarkCommand;
use App\Modules\Compensation\Livewire\Dashboard;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Support\Facades\Schema;

class CompensationRenderBenchmarkCommand extends AbstractRenderBenchmarkCommand
{
    protected $signature = 'compensation:render-benchmark
        {--scales-response-budget= : Max initial response size in bytes}
        {--scales-render-budget= : Max render time in ms}
        {--json : Print report as JSON}';

    protected $description = 'Benchmark Livewire render time and payload size for Compensation flows';

    public function handle(LivewireComponentProfiler $profiler): int
    {
        if (! Schema::hasTable('pay_scales')) {
            $this->error('Compensation tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $user = $this->resolveUserForPermissions('show-compensation');

        if (! $user) {
            $this->error('No user with Compensation view access was found.');

            return self::FAILURE;
        }

        $budget = [
            'response_bytes' => (int) ($this->option('scales-response-budget') ?: 200000),
            'render_ms' => (float) ($this->option('scales-render-budget') ?: 120),
        ];

        $results = [];
        $results[] = $this->probe('scales_render', $budget, fn () => $profiler->measureRender($user, Dashboard::class, queryParams: ['tab' => 'scales']));
        $results[] = $this->probe('components_render', $budget, fn () => $profiler->measureRender($user, Dashboard::class, queryParams: ['tab' => 'components']));

        $failed = collect($results)->where('status', 'failed')->count();
        $overBudget = collect($results)->where('over_budget', true)->count();

        if ($this->option('json')) {
            $this->line((string) json_encode(['results' => $results], JSON_PRETTY_PRINT));
        } else {
            foreach ($results as $result) {
                $this->line(sprintf('%s: %s ms, %s bytes%s', $result['flow'], $result['render_ms'], $result['response_bytes'], $result['over_budget'] ? ' OVER' : ''));
            }
        }

        return ($failed === 0 && $overBudget === 0) ? self::SUCCESS : self::FAILURE;
    }
}
