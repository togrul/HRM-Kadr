<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeLifecycleQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_lifecycle_query_budget_passes_with_seeded_templates(): void
    {
        $exitCode = Artisan::call('employee-lifecycle:query-budget', [
            '--json' => true,
            '--dashboard-budget' => 45,
            '--events-budget' => 20,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame(4, DB::table('employee_lifecycle_plan_templates')->count());
        $this->assertSame(['movement', 'offboarding', 'onboarding', 'probation'], DB::table('employee_lifecycle_plan_templates')->orderBy('type')->pluck('type')->all());
    }
}
