<?php

namespace App\Modules\Reports\Application\Jobs;

use App\Modules\Reports\Application\Services\ScheduledReportRunner;
use App\Modules\Reports\Domain\Models\ReportSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bir planlı hesabatı qurub göndərir. `QUEUE_CONNECTION=sync`-də dispatch anında icra olunur,
 * `database`/`redis`-də worker icra edir. Runner xətanı özü tutub qeyd edir — təkrar cəhd
 * edilmir ki, alıcılar eyni hesabatı iki dəfə almasın (tries=1).
 */
class SendScheduledReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public readonly int $scheduleId)
    {
        $queue = config('reports.schedules.queue');
        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(ScheduledReportRunner $runner): void
    {
        $schedule = ReportSchedule::query()->find($this->scheduleId);

        if ($schedule === null) {
            return;
        }

        $runner->run($schedule);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('reports.schedule.job_failed', [
            'schedule_id' => $this->scheduleId,
            'error' => $exception?->getMessage(),
        ]);

        ReportSchedule::query()->whereKey($this->scheduleId)->update([
            'last_status' => ReportSchedule::STATUS_FAILED,
            'last_error' => mb_substr((string) $exception?->getMessage(), 0, 1000),
        ]);
    }
}
