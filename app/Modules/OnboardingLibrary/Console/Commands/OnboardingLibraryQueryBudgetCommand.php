<?php

namespace App\Modules\OnboardingLibrary\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Modules\OnboardingLibrary\Application\Services\OnboardingLibraryReadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnboardingLibraryQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'onboarding-library:query-budget
        {--allow-empty : Return success when onboarding dataset is empty}
        {--general-budget= : Max query count for general tab}
        {--library-budget= : Max query count for library tab}
        {--reports-budget= : Max query count for reports tab}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Onboarding Library dashboard flows';

    public function handle(OnboardingLibraryReadService $service): int
    {
        if (! $this->hasRequiredTables()) {
            $this->error('Onboarding Library tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $hasData = DB::table('onboarding_document_templates')->exists()
            || DB::table('onboarding_document_assignments')->exists();

        if (! $hasData && (bool) $this->option('allow-empty')) {
            $payload = [
                'summary' => [
                    'failed_probes' => 0,
                    'over_budget_probes' => 0,
                    'passed_probes' => 0,
                    'skipped' => true,
                    'reason' => 'onboarding_library_dataset_empty',
                ],
                'results' => [],
            ];

            return $this->outputPayload($payload);
        }

        $budgets = [
            'general_build' => max(1, (int) ($this->option('general-budget') ?: 16)),
            'library_build' => max(1, (int) ($this->option('library-budget') ?: 10)),
            'reports_build' => max(1, (int) ($this->option('reports-budget') ?: 10)),
        ];

        $results = [
            $this->probe('general_build', $budgets['general_build'], fn () => $service->buildGeneral('', '', '', 'onboardingLibraryQueryBudgetPage')),
            $this->probe('library_build', $budgets['library_build'], fn () => $service->buildLibrary('')),
            $this->probe('reports_build', $budgets['reports_build'], fn () => $service->buildReports()),
        ];

        $payload = [
            'summary' => [
                'failed_probes' => collect($results)->where('status', 'failed')->count(),
                'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
                'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
            ],
            'results' => $results,
        ];

        return $this->outputPayload($payload);
    }

    private function hasRequiredTables(): bool
    {
        foreach (['onboarding_document_templates', 'onboarding_document_assignments', 'personnels', 'structures', 'positions'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function outputPayload(array $payload): int
    {
        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['flow', 'status', 'queries', 'budget', 'over_budget', 'elapsed_ms', 'db_time_ms', 'error'],
                collect($payload['results'])->map(fn (array $result) => [
                    $result['flow'],
                    $result['status'],
                    $result['queries'],
                    $result['budget'],
                    $result['over_budget'] ? 'yes' : 'no',
                    $result['elapsed_ms'],
                    $result['db_time_ms'],
                    $result['error'] ?? '-',
                ])->all()
            );
        }

        return ($payload['summary']['failed_probes'] === 0 && $payload['summary']['over_budget_probes'] === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }
}
