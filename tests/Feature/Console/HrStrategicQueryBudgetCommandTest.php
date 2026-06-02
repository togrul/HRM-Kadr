<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrStrategicQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_strategic_hr_query_budget_command_passes_for_core_probes(): void
    {
        $this->artisan('hr:strategic-query-budget', [
            '--lifecycle-budget' => 80,
            '--compliance-budget' => 80,
            '--ats-budget' => 25,
            '--json' => true,
        ])
            ->assertExitCode(0);
    }
}
