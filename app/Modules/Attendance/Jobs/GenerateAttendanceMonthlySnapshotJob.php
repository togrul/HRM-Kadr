<?php

namespace App\Modules\Attendance\Jobs;

use App\Modules\Attendance\Application\Services\AttendanceMonthLockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAttendanceMonthlySnapshotJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $year,
        public int $month,
        public bool $lock = false
    ) {
    }

    public function handle(AttendanceMonthLockService $service): void
    {
        $service->snapshotMonth($this->year, $this->month, $this->lock);
    }
}

