<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReportsQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_skip_when_dataset_is_empty_and_allow_empty_enabled(): void
    {
        $exitCode = Artisan::call('reports:query-budget', [
            '--allow-empty' => true,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertTrue((bool) data_get($payload, 'summary.skipped'));
        $this->assertSame('reports_dataset_empty', data_get($payload, 'summary.reason'));
    }

    public function test_it_reports_all_reports_query_flows(): void
    {
        $exitCode = Artisan::call('reports:query-budget', [
            '--overview-budget' => 200,
            '--standard-budget' => 200,
            '--dynamic-budget' => 200,
            '--comparisons-budget' => 200,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame(4, data_get($payload, 'summary.passed_probes'));
        $this->assertSame('overview_build', data_get($payload, 'results.0.flow'));
        $this->assertSame('standard_headcount_build', data_get($payload, 'results.1.flow'));
        $this->assertSame('dynamic_build', data_get($payload, 'results.2.flow'));
        $this->assertSame('comparisons_build', data_get($payload, 'results.3.flow'));
    }
}
