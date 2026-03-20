<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Modules\Personnel\Application\Services\ProfessionalPortfolioRegistrySyncService;
use Illuminate\Console\Command;

class ProfessionalPortfolioSyncRegistriesCommand extends Command
{
    protected $signature = 'personnel:portfolio-sync-registries {--json : Print report as JSON}';

    protected $description = 'Sync professional portfolio master registries from personnel-first records.';

    public function handle(ProfessionalPortfolioRegistrySyncService $service): int
    {
        $result = $service->syncAll();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['Registry', 'Synced'], [
            ['Events', $result['events']],
            ['Media outlets', $result['media_outlets']],
            ['Projects', $result['projects']],
        ]);

        return self::SUCCESS;
    }
}
