<?php

namespace App\Modules\Attendance\Jobs;

use App\Modules\Attendance\Application\Services\AttendancePunchProcessingPipelineService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateAttendanceLedgersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int,string>  $tabelNos
     */
    public function __construct(
        public string $fromDate,
        public string $toDate,
        public ?string $source = null,
        public ?int $structureId = null,
        public array $tabelNos = []
    ) {
    }

    public function handle(AttendancePunchProcessingPipelineService $pipeline): void
    {
        $pipeline->process(
            from: Carbon::parse($this->fromDate)->startOfDay(),
            to: Carbon::parse($this->toDate)->endOfDay(),
            source: $this->source,
            options: [
                'include_processed' => true,
                'mark_processed' => false,
                'structure_id' => $this->structureId,
                'tabel_nos' => $this->tabelNos,
            ]
        );
    }
}

