<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Services\Orders\OrderTemplateMetadataSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTemplateMetadataSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_metadata_from_fallback_tokens_when_template_placeholders_are_unavailable(): void
    {
        [$orderType, $version] = $this->seedOrderTypeAndVersion('Seed');

        $result = app(OrderTemplateMetadataSyncService::class)->sync(
            $version,
            (int) $orderType->id,
            (string) $orderType->order?->blade,
            true,
            1
        );

        $this->assertSame(8, $result['token_count']);
        $this->assertSame(8, $result['created_fields']);
        $this->assertSame(8, $result['created_mappings']);
        $this->assertSame(0, $result['deleted_fields']);
        $this->assertSame(0, $result['deleted_mappings']);

        $this->assertDatabaseHas('order_template_fields', [
            'order_template_version_id' => (int) $version->id,
            'field_key' => 'structure',
            'field_type' => 'relation',
        ]);
        $this->assertDatabaseHas('order_template_mappings', [
            'order_template_version_id' => (int) $version->id,
            'placeholder' => '$structure',
            'field_key' => 'structure',
            'scope' => 'row',
        ]);
    }

    public function test_strict_sync_removes_stale_row_fields_and_mappings(): void
    {
        [$orderType, $version] = $this->seedOrderTypeAndVersion('Strict');

        OrderTemplateField::query()->create([
            'order_template_version_id' => (int) $version->id,
            'field_key' => 'obsolete_field',
            'label' => 'Obsolete field',
            'field_type' => 'text',
            'is_required' => false,
            'sort_order' => 990,
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => (int) $version->id,
            'placeholder' => '$obsolete_field',
            'field_key' => 'obsolete_field',
            'scope' => 'row',
            'sort_order' => 990,
        ]);

        $result = app(OrderTemplateMetadataSyncService::class)->sync(
            $version,
            (int) $orderType->id,
            (string) $orderType->order?->blade,
            true,
            1
        );

        $this->assertSame(1, $result['deleted_fields']);
        $this->assertSame(1, $result['deleted_mappings']);

        $this->assertDatabaseMissing('order_template_fields', [
            'order_template_version_id' => (int) $version->id,
            'field_key' => 'obsolete_field',
        ]);
        $this->assertDatabaseMissing('order_template_mappings', [
            'order_template_version_id' => (int) $version->id,
            'placeholder' => '$obsolete_field',
            'scope' => 'row',
        ]);
    }

    /**
     * @return array{0:OrderType,1:OrderTemplateVersion}
     */
    private function seedOrderTypeAndVersion(string $suffix): array
    {
        OrderCategory::query()->firstOrCreate([
            'id' => 9800,
        ], [
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        $orderId = 9801 + random_int(1, 500);
        app(\App\Services\Orders\TemplateAdminService::class)->create([
            'id' => $orderId,
            'order_category_id' => 9800,
            'name' => "Order {$suffix}",
            'content' => '',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        $orderType = OrderType::query()->create([
            'order_id' => $orderId,
            'name' => "Type {$suffix}",
        ]);

        $set = OrderTemplateSet::query()->create([
            'order_type_id' => (int) $orderType->id,
            'name' => "Set {$suffix}",
        ]);

        $version = OrderTemplateVersion::query()->create([
            'order_template_set_id' => (int) $set->id,
            'version_no' => 1,
            'template_name' => "Template {$suffix}",
            'template_path' => '',
            'status' => 'draft',
            'is_active' => true,
            'published_at' => null,
            'meta' => [],
        ]);

        return [$orderType, $version];
    }
}

