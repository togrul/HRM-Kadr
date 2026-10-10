<?php

namespace App\Modules\Orders\Console\Commands;

use App\Modules\Orders\Application\Services\DeferredTransferService;
use App\Services\Modules\ModuleState;
use Illuminate\Console\Command;

class ApplyDueTransfersCommand extends Command
{
    protected $signature = 'orders:apply-due-transfers';

    protected $description = 'Təsdiqlənmiş köçürmə əmrlərini qüvvəyə minmə günündə tətbiq edir';

    public function handle(DeferredTransferService $transfers, ModuleState $modules): int
    {
        $stats = $modules->enabled('orders') ? $transfers->applyDue() : ['applied' => 0, 'skipped' => 0, 'failed' => 0];
        $this->line(json_encode($stats, JSON_THROW_ON_ERROR));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
