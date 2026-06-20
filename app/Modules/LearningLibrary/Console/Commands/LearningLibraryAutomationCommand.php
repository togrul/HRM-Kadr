<?php

namespace App\Modules\LearningLibrary\Console\Commands;

use App\Modules\LearningLibrary\Application\Services\LearningLibraryAutomationService;
use Illuminate\Console\Command;

class LearningLibraryAutomationCommand extends Command
{
    protected $signature = 'learning-library:automation {--json}';

    protected $description = 'Run learning library auto-assignment, overdue update, and reminder automation.';

    public function handle(LearningLibraryAutomationService $service): int
    {
        $result = $service->run();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Learning automation completed.');
        $this->table(['Metric', 'Value'], collect($result)->map(fn ($value, $key) => [$key, $value]));

        return self::SUCCESS;
    }
}
