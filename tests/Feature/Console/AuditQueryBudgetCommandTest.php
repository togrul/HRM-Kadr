<?php

namespace Tests\Feature\Console;

use App\Models\AuditActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_query_budget_command_passes_for_dashboard_render(): void
    {
        AuditActivity::query()->create([
            'log_name' => 'auth',
            'description' => 'User logged in',
            'event' => 'login',
            'causer_type' => User::class,
            'causer_id' => 1,
            'properties' => ['ip' => '127.0.0.1'],
        ]);

        $this->artisan('audit:query-budget', [
            '--render-budget' => 25,
            '--json' => true,
        ])->assertExitCode(0);
    }

    public function test_audit_retention_status_command_reports_configuration(): void
    {
        $this->artisan('audit:retention-status', [
            '--json' => true,
        ])
            ->expectsOutputToContain('"scheduled_command": "activitylog:clean --force"')
            ->assertExitCode(0);
    }
}
