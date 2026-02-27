<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderTemplateVersionAudit;
use App\Models\OrderType;
use App\Services\Orders\OrderTemplateAuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTemplateAuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_template_version_audit_event(): void
    {
        $version = $this->makeTemplateVersion();

        app(OrderTemplateAuditLogger::class)->log((int) $version->id, 'ui_config_saved', [
            'fields_count' => 5,
            'mappings_count' => 5,
        ]);

        $this->assertDatabaseHas('order_template_version_audits', [
            'order_template_version_id' => $version->id,
            'action' => 'ui_config_saved',
        ]);

        $audit = OrderTemplateVersionAudit::query()->first();
        $this->assertSame(5, data_get($audit?->payload, 'fields_count'));
        $this->assertSame(5, data_get($audit?->payload, 'mappings_count'));
    }

    public function test_it_truncates_oversized_payload(): void
    {
        $version = $this->makeTemplateVersion();
        $tooLarge = str_repeat('x', 70000);

        app(OrderTemplateAuditLogger::class)->log($version, 'metadata_field_added', [
            'debug' => $tooLarge,
        ]);

        $audit = OrderTemplateVersionAudit::query()->first();
        $this->assertTrue((bool) data_get($audit?->payload, '_meta.truncated', false));
        $this->assertSame(strlen(json_encode(['debug' => $tooLarge])), (int) data_get($audit?->payload, '_meta.original_bytes'));
        $this->assertNull(data_get($audit?->payload, 'debug'));
    }

    private function makeTemplateVersion(): OrderTemplateVersion
    {
        OrderCategory::query()->create([
            'id' => 9901,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        Order::query()->create([
            'id' => 9902,
            'order_category_id' => 9901,
            'name' => 'Template',
            'content' => 'templates/test.docx',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        $orderType = OrderType::query()->create([
            'order_id' => 9902,
            'name' => 'Type A',
        ]);

        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Set',
        ]);

        return OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_name' => 'V1',
            'template_path' => 'templates/test.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);
    }
}
