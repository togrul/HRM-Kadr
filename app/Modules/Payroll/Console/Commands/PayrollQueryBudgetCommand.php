<?php

namespace App\Modules\Payroll\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Schema;

class PayrollQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'payroll:query-budget
        {--runs-budget= : Max query count for the runs flow}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Payroll dashboard flows';

    public function handle(): int
    {
        if (! Schema::hasTable('payroll_runs')) {
            $this->error('Payroll tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $budget = $this->budget('runs-budget', 'payroll.performance.query_budget.runs_build', 10);

        $result = $this->probe('runs_build', $budget, function (): void {
            PayrollRun::query()->with(['period', 'regime'])->orderByDesc('id')->limit(40)->get();
        });

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $this->line(sprintf('runs_build: %d/%d queries%s', $result['queries'], $result['budget'], $result['over_budget'] ? ' OVER' : ''));
        }

        return ($result['status'] === 'ok' && ! $result['over_budget']) ? self::SUCCESS : self::FAILURE;
    }
}
