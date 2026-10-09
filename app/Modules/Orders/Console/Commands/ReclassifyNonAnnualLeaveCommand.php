<?php

namespace App\Modules\Orders\Console\Commands;

use App\Modules\Orders\Infrastructure\Document\NonAnnualLeaveReclassifier;
use Illuminate\Console\Command;

/**
 * Moves the standard paternity, education and unpaid leave order types off the annual
 * leave effect and gives back the days their approved orders took from the stored
 * yearly balance (ƏM m.112.1, m.123, m.125.4, m.128–130). Safe to run again: a type
 * already moved is skipped. The Orders migration runs it once on deploy.
 */
class ReclassifyNonAnnualLeaveCommand extends Command
{
    protected $signature = 'orders:reclassify-non-annual-leave {--dry-run : Report what would change without writing}';

    protected $description = 'Stop paternity, education and unpaid leave orders from consuming the annual leave balance';

    public function handle(NonAnnualLeaveReclassifier $reclassifier): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $report = $reclassifier->run($dryRun);

        $this->info(sprintf(
            '%sOrder types reclassified: %d; approved orders restored: %d; days returned to the annual balance: %d.',
            $dryRun ? '[dry-run] ' : '',
            $report['templates'],
            $report['orders'],
            $report['days'],
        ));

        return self::SUCCESS;
    }
}
