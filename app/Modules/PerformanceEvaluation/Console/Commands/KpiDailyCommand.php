<?php

namespace App\Modules\PerformanceEvaluation\Console\Commands;

use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardLifecycleService;
use Illuminate\Console\Command;

class KpiDailyCommand extends Command
{
    protected $signature = 'performance:kpi-daily';

    protected $description = 'Close KPI cards of people who left or moved, auto-accept overdue agreements, send deadline reminders and escalations';

    public function handle(ScorecardLifecycleService $lifecycle): int
    {
        $people = $lifecycle->syncPersonnel();
        $deadlines = $lifecycle->runDeadlines();

        $this->table(['step', 'count'], collect([...$people, ...$deadlines])->map(fn (int $count, string $step): array => [$step, $count])->values()->all());

        return self::SUCCESS;
    }
}
