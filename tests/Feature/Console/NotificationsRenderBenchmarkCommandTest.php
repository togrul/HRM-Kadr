<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NotificationsRenderBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_render_metrics_for_notification_settings_islands(): void
    {
        User::factory()->create(['is_active' => true]);

        $exitCode = Artisan::call('notifications:render-benchmark', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame('ok', data_get($results, 'settings_shell_render.status'));
        $this->assertSame('ok', data_get($results, 'overview_panel_render.status'));
        $this->assertSame('ok', data_get($results, 'analytics_panel_render.status'));
        $this->assertSame('ok', data_get($results, 'history_board_render.status'));
        $this->assertSame('ok', data_get($results, 'template_manager_render.status'));
        $this->assertSame('ok', data_get($results, 'rule_manager_render.status'));
        $this->assertSame('ok', data_get($results, 'approval_queue_render.status'));
        $this->assertSame('ok', data_get($results, 'announcement_composer_render.status'));
        $this->assertSame('ok', data_get($results, 'campaign_board_render.status'));
        $this->assertGreaterThan(0, (int) data_get($results, 'settings_shell_render.response_bytes'));
    }
}
