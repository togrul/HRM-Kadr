<?php

namespace App\Modules\Compensation\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Models\CompensationComponent;
use App\Models\PayScale;
use Illuminate\Support\Facades\Schema;

class CompensationQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'compensation:query-budget
        {--scales-budget= : Max query count for the scales flow}
        {--components-budget= : Max query count for the components flow}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Compensation dashboard flows';

    public function handle(): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Compensation tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $budgets = [
            'scales_build' => $this->budget('scales-budget', 'compensation.performance.query_budget.scales_build', 12),
            'components_build' => $this->budget('components-budget', 'compensation.performance.query_budget.components_build', 8),
        ];

        $results = [];

        $results[] = $this->probe('scales_build', $budgets['scales_build'], function (): void {
            PayScale::query()->with('regime:id,name')->withCount('grades')->orderByDesc('effective_from')->limit(8)->get();
        });

        $results[] = $this->probe('components_build', $budgets['components_build'], function (): void {
            CompensationComponent::query()->orderBy('sort')->limit(10)->get();
        });

        $failed = collect($results)->where('status', 'failed')->count();
        $overBudget = collect($results)->where('over_budget', true)->count();

        if ($this->option('json')) {
            $this->line((string) json_encode(['results' => $results], JSON_PRETTY_PRINT));
        } else {
            foreach ($results as $result) {
                $this->line(sprintf('%s: %d/%d queries%s', $result['flow'], $result['queries'], $result['budget'], $result['over_budget'] ? ' OVER' : ''));
            }
        }

        return ($failed === 0 && $overBudget === 0) ? self::SUCCESS : self::FAILURE;
    }

    private function hasRequiredTables(): bool
    {
        foreach (['pay_scales', 'compensation_components'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
