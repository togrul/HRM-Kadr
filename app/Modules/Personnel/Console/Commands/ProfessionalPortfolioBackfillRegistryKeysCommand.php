<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Models\PersonnelEventRecord;
use App\Models\PersonnelMediaMention;
use App\Models\PersonnelProjectRecord;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioRegistryFingerprintService;
use Illuminate\Console\Command;

class ProfessionalPortfolioBackfillRegistryKeysCommand extends Command
{
    protected $signature = 'personnel:portfolio-backfill-registry {--json : Print report as JSON}';

    protected $description = 'Backfill Professional Portfolio registry fingerprint columns.';

    public function handle(ProfessionalPortfolioRegistryFingerprintService $service): int
    {
        $updated = [
            'events' => 0,
            'media' => 0,
            'projects' => 0,
        ];

        PersonnelEventRecord::query()->chunkById(200, function ($records) use (&$updated, $service) {
            foreach ($records as $record) {
                $record->forceFill([
                    'registry_key' => $service->forEvent($record->toArray()),
                ])->save();
                $updated['events']++;
            }
        });

        PersonnelMediaMention::query()->chunkById(200, function ($records) use (&$updated, $service) {
            foreach ($records as $record) {
                $record->forceFill([
                    'publisher_registry_key' => $service->forMediaPublisher($record->toArray()),
                ])->save();
                $updated['media']++;
            }
        });

        PersonnelProjectRecord::query()->chunkById(200, function ($records) use (&$updated, $service) {
            foreach ($records as $record) {
                $record->forceFill([
                    'registry_key' => $service->forProject($record->toArray()),
                ])->save();
                $updated['projects']++;
            }
        });

        if ($this->option('json')) {
            $this->line(json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            foreach ($updated as $key => $count) {
                $this->line(ucfirst($key).": {$count}");
            }
        }

        return self::SUCCESS;
    }
}
