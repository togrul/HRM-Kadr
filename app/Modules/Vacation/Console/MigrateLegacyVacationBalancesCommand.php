<?php

namespace App\Modules\Vacation\Console;

use App\Modules\Vacation\Application\Services\LegacyVacationMigrator;
use Illuminate\Console\Command;

/**
 * Köhnə təqvim ili balanslarının (`vacations`) iş ili uçotuna köçürülməsi və ya geri qaytarılması.
 * Miqrasiya bunu bir dəfə edir; sonradan əlavə olunmuş köhnə sətirlər üçün təkrar işlədilə bilər.
 */
class MigrateLegacyVacationBalancesCommand extends Command
{
    protected $signature = 'vacation:migrate-legacy
        {--dry-run : Yalnız say göstər, yazma}
        {--rollback : Köçürülmüş iş illərini sil və işarələri götür}';

    protected $description = 'Move calendar-year vacation balances into the work-year ledger (or roll it back)';

    public function handle(LegacyVacationMigrator $migrator): int
    {
        if ($this->option('rollback')) {
            $result = $migrator->rollback();
            $this->info(sprintf('Silinən hərəkət: %d, iş ili: %d, işarəsi götürülən sətir: %d', $result['entries'], $result['work_years'], $result['rows']));

            return self::SUCCESS;
        }

        $result = $migrator->migrate((bool) $this->option('dry-run'));
        $this->info(sprintf('İşçi: %d, köçürülən: %d, buraxılan: %d', $result['employees'], $result['migrated'], $result['skipped']));

        return self::SUCCESS;
    }
}
