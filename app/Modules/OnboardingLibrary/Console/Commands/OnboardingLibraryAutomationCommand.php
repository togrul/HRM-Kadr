<?php

namespace App\Modules\OnboardingLibrary\Console\Commands;

use App\Modules\OnboardingLibrary\Application\Services\OnboardingLibraryAutomationService;
use Illuminate\Console\Command;

class OnboardingLibraryAutomationCommand extends Command
{
    protected $signature = 'onboarding-library:automation {--json}';

    protected $description = 'Run onboarding auto-assignment, overdue update, and reminder automation.';

    public function handle(OnboardingLibraryAutomationService $service): int
    {
        $result = $service->run();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Onboarding automation completed.');
        $this->table(['Metric', 'Value'], collect($result)->map(fn ($value, $key) => [$key, $value]));

        return self::SUCCESS;
    }
}
