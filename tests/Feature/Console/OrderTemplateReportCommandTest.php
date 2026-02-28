<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderTemplateReportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_writes_report_to_log_channel(): void
    {
        Storage::fake('local');

        config()->set('orders.observability.reports.channels', ['log']);
        config()->set('orders.observability.reports.log_file', 'logs/orders-template-metrics.log');

        $exitCode = Artisan::call('orders:templates:report', [
            '--days' => 1,
            '--allow-empty-budget' => true,
            '--json' => true,
        ]);
        $raw = Storage::disk('local')->get('logs/orders-template-metrics.log');
        $line = trim((string) collect(explode(PHP_EOL, $raw))->filter()->last());
        $payload = json_decode($line, true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertSame(0, (int) data_get($payload, 'metrics.summary.failed', 0));
        $this->assertArrayHasKey('query_budget', $payload);
        Storage::disk('local')->assertExists('logs/orders-template-metrics.log');
    }
}
