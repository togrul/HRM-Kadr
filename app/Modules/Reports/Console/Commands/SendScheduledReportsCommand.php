<?php

namespace App\Modules\Reports\Console\Commands;

use App\Modules\Reports\Application\Jobs\SendScheduledReportJob;
use App\Modules\Reports\Domain\Models\ReportSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Vaxtı çatmış aktiv planlı hesabatları tapır və hər biri üçün SendScheduledReportJob göndərir.
 * Plan əvvəlcə «tutulur» (next_run_at şərti yenilənir) ki, üst-üstə düşən iki icra eyni
 * hesabatı iki dəfə göndərməsin.
 */
class SendScheduledReportsCommand extends Command
{
    protected $signature = 'reports:send-scheduled {--dry-run : Yalnız vaxtı çatmış planları göstər}';

    protected $description = 'Vaxtı çatmış planlı hesabatları qurub email ilə göndərir';

    public function handle(): int
    {
        // Miqrasiya hələ işlənməyibsə scheduler hər 5 dəqiqədə xəta atmasın.
        if (! Schema::hasTable('report_schedules')) {
            $this->warn('report_schedules table is missing — run php artisan migrate.');

            return self::SUCCESS;
        }

        $now = now();
        $limit = max(1, (int) config('reports.schedules.batch_limit', 100));

        $due = ReportSchedule::query()
            ->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();

        $dispatched = 0;

        foreach ($due as $schedule) {
            if ($this->option('dry-run')) {
                $this->line(sprintf('#%d %s (%s)', $schedule->id, $schedule->name, $schedule->next_run_at?->toDateTimeString()));

                continue;
            }

            $claimed = ReportSchedule::query()
                ->whereKey($schedule->id)
                ->where('next_run_at', $schedule->next_run_at)
                ->update(['next_run_at' => $schedule->nextRunAfter($now)]);

            if ($claimed !== 1) {
                continue;
            }

            SendScheduledReportJob::dispatch((int) $schedule->id);
            $dispatched++;
        }

        $this->info(sprintf('Due: %d, dispatched: %d', $due->count(), $dispatched));

        return self::SUCCESS;
    }
}
