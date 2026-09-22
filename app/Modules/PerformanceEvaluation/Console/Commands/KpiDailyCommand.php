<?php

namespace App\Modules\PerformanceEvaluation\Console\Commands;

use App\Modules\PerformanceEvaluation\Application\Services\Kpi\InternalKpiMetrics;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardLifecycleService;
use Illuminate\Console\Command;

class KpiDailyCommand extends Command
{
    protected $signature = 'performance:kpi-daily';

    protected $description = 'Close KPI cards of people who left or moved, auto-accept overdue agreements, send deadline reminders and escalations, scale cards for long leave, refill KPIs from HRM data';

    public function handle(ScorecardLifecycleService $lifecycle, InternalKpiMetrics $metrics): int
    {
        $people = $lifecycle->syncPersonnel();
        $people['long_leave'] = $lifecycle->syncLongLeave();
        $deadlines = $lifecycle->runDeadlines();
        $deadlines['internal_metrics'] = $metrics->sync();

        $this->table(['step', 'count'], collect([...$people, ...$deadlines])->map(fn (int $count, string $step): array => [$step, $count])->values()->all());

        return self::SUCCESS;
    }
}
