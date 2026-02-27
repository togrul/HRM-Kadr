<?php

namespace Tests\Feature\Console;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OrderTemplateReadinessReportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_metadata_and_legacy_statuses(): void
    {
        [$metadataType, $legacyType] = $this->seedOrderTypes();
        $this->attachMetadataVersion($metadataType);

        $exitCode = Artisan::call('orders:templates:readiness');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('metadata_ready', $output);
        $this->assertStringContainsString('legacy_fallback', $output);
        $this->assertStringContainsString('order_types_total', $output);
        $this->assertStringContainsString((string) $metadataType->name, $output);
        $this->assertStringContainsString((string) $legacyType->name, $output);
    }

    public function test_it_can_print_json_summary(): void
    {
        [$metadataType] = $this->seedOrderTypes();
        $this->attachMetadataVersion($metadataType);

        $exitCode = Artisan::call('orders:templates:readiness', ['--json' => true]);
        $output = Artisan::output();
        $decoded = json_decode($output, true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($decoded);
        $this->assertSame(2, data_get($decoded, 'summary.order_types_total'));
        $this->assertSame(1, data_get($decoded, 'summary.metadata_ready'));
        $this->assertSame(1, data_get($decoded, 'summary.legacy_fallback'));
    }

    private function seedOrderTypes(): array
    {
        OrderCategory::query()->create([
            'id' => 9300,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        Order::query()->create([
            'id' => 9301,
            'order_category_id' => 9300,
            'name' => 'İşə qəbul',
            'content' => 'templates/default.docx',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        Order::query()->create([
            'id' => 9302,
            'order_category_id' => 9300,
            'name' => 'Məzuniyyət',
            'content' => 'templates/vacation.docx',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_VACATION,
        ]);

        $metadataType = OrderType::query()->create([
            'order_id' => 9301,
            'name' => 'İşə qəbul',
        ]);

        $legacyType = OrderType::query()->create([
            'order_id' => 9302,
            'name' => 'Məzuniyyət',
        ]);

        return [$metadataType, $legacyType];
    }

    private function attachMetadataVersion(OrderType $orderType): void
    {
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

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$fullname',
            'label' => 'Tam ad',
            'field_type' => 'text',
            'sort_order' => 10,
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '${fullname}',
            'field_key' => '$fullname',
            'scope' => 'row',
            'sort_order' => 10,
        ]);
    }
}
