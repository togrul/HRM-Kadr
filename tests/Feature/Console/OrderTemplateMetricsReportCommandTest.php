<?php

namespace Tests\Feature\Console;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderGenerationLog;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OrderTemplateMetricsReportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_generation_metrics_and_version_usage(): void
    {
        [$orderType, $version] = $this->seedTemplateVersion();

        foreach ([
            ['status' => 'success', 'duration_ms' => 100],
            ['status' => 'success', 'duration_ms' => 200],
            ['status' => 'success', 'duration_ms' => 500],
            ['status' => 'success', 'duration_ms' => 1000],
            ['status' => 'failed', 'duration_ms' => 300],
        ] as $index => $row) {
            OrderGenerationLog::query()->create([
                'render_id' => 'render-'.$index,
                'order_type_id' => $orderType->id,
                'order_template_version_id' => $version->id,
                'status' => $row['status'],
                'duration_ms' => $row['duration_ms'],
                'created_at' => now()->subHours(1),
            ]);
        }

        $exitCode = Artisan::call('orders:templates:metrics', [
            '--days' => 7,
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertSame(5, data_get($payload, 'summary.total'));
        $this->assertSame(4, data_get($payload, 'summary.success'));
        $this->assertSame(1, data_get($payload, 'summary.failed'));
        $this->assertEquals(20.0, data_get($payload, 'summary.generation_error_rate_pct'));
        $this->assertSame(1000, data_get($payload, 'summary.slow_render_p95_ms'));
        $this->assertSame(1000, data_get($payload, 'summary.slow_render_p99_ms'));
        $this->assertSame(1, data_get($payload, 'summary.version_usage_count'));
        $this->assertSame($version->id, data_get($payload, 'version_usage.0.order_template_version_id'));
        $this->assertSame($orderType->id, data_get($payload, 'version_usage.0.order_type_id'));
        $this->assertTrue((bool) data_get($payload, 'gate.ok'));
    }

    public function test_it_fails_when_error_rate_exceeds_threshold(): void
    {
        [$orderType, $version] = $this->seedTemplateVersion();

        OrderGenerationLog::query()->create([
            'render_id' => 'render-threshold-1',
            'order_type_id' => $orderType->id,
            'order_template_version_id' => $version->id,
            'status' => 'failed',
            'duration_ms' => 200,
            'created_at' => now()->subMinutes(10),
        ]);

        $exitCode = Artisan::call('orders:templates:metrics', [
            '--days' => 7,
            '--json' => true,
            '--max-error-rate' => 0,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertFalse((bool) data_get($payload, 'gate.ok'));
        $this->assertNotEmpty(data_get($payload, 'gate.reasons'));
    }

    /**
     * @return array{0: \App\Models\OrderType, 1: \App\Models\OrderTemplateVersion}
     */
    private function seedTemplateVersion(): array
    {
        OrderCategory::query()->create([
            'id' => 9400,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        $order = app(\App\Services\Orders\TemplateAdminService::class)->create([
            'id' => 9401,
            'order_category_id' => 9400,
            'name' => 'İşə qəbul',
            'content' => 'templates/ignored.docx',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        $orderType = OrderType::query()->create([
            'order_id' => $order->id,
            'name' => 'İşə qəbul',
        ]);

        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Default set',
        ]);

        $version = OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_name' => 'v1',
            'template_path' => 'templates/default-v1.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        return [$orderType, $version];
    }
}
