<?php

namespace App\Modules\Attendance\Jobs;

use App\Modules\Attendance\Application\Services\AttendancePunchProcessingPipelineService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAttendancePunchesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $fromDate,
        public string $toDate,
        public ?string $source = null
    ) {
    }

    public function handle(AttendancePunchProcessingPipelineService $pipeline): void
    {
        $pipeline->process(
            from: Carbon::parse($this->fromDate)->startOfDay(),
            to: Carbon::parse($this->toDate)->endOfDay(),
            source: $this->source
        );
    }
}

