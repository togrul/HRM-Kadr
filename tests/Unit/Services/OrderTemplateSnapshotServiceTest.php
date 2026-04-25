<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Services\Orders\OrderTemplateSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrderTemplateSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_throws_when_strict_mode_enabled_and_no_active_version_exists(): void
    {
        config()->set('orders.engine.strict_mode', true);

        $orderType = $this->seedOrderType('StrictMissingVersion', 'templates/legacy-missing.docx');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(__('orders::template_runtime.messages.active_metadata_version_not_found_for_order_type'));

        app(OrderTemplateSnapshotService::class)->capture((int) $orderType->id);
    }

    public function test_it_throws_when_strict_mode_enabled_and_active_version_has_no_row_mappings(): void
    {
        config()->set('orders.engine.strict_mode', true);

        $orderType = $this->seedOrderType('StrictNoMappings', 'templates/legacy-no-map.docx');
        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Strict set',
        ]);

        OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_path' => 'templates/strict-no-map.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(__('orders::template_runtime.messages.metadata_mappings_required_for_order_type'));

        app(OrderTemplateSnapshotService::class)->capture((int) $orderType->id);
    }

    public function test_it_captures_metadata_snapshot_in_strict_mode_when_version_is_ready(): void
    {
        config()->set('orders.engine.strict_mode', true);

        $orderType = $this->seedOrderType('StrictReady', 'templates/legacy-ready.docx');
        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Ready set',
        ]);

        $version = OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_path' => 'templates/strict-ready.docx',
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

        $snapshot = app(OrderTemplateSnapshotService::class)->capture((int) $orderType->id);

        $this->assertSame($version->id, $snapshot['order_template_version_id']);
        $this->assertSame('metadata', $snapshot['template_render_mode']);
        $this->assertSame('metadata', data_get($snapshot, 'template_snapshot.render_mode'));
        $this->assertSame('templates/strict-ready.docx', data_get($snapshot, 'template_snapshot.template_path'));
    }

    public function test_it_hydrates_snapshot_relations_without_mass_assignment_errors(): void
    {
        $service = app(OrderTemplateSnapshotService::class);

        $version = $service->versionFromSnapshot([
            'version' => [
                'id' => 77,
                'order_template_set_id' => 8,
                'version_no' => 2,
                'status' => 'published',
                'is_active' => true,
                'meta' => [],
            ],
            'template_path' => 'templates/test.docx',
            'fields' => [[
                'id' => 101,
                'order_template_version_id' => 77,
                'field_key' => 'structure',
                'label' => 'İdarə',
                'field_type' => 'relation',
                'is_required' => false,
                'sort_order' => 10,
                'ui_config' => [],
            ]],
            'mappings' => [[
                'id' => 201,
                'order_template_version_id' => 77,
                'placeholder' => '$structure',
                'field_key' => 'structure',
                'scope' => 'row',
                'sort_order' => 10,
            ]],
        ]);

        $this->assertNotNull($version);
        $this->assertCount(1, $version->fields);
        $this->assertCount(1, $version->mappings);
        $this->assertSame(101, (int) $version->fields->first()->id);
        $this->assertSame(201, (int) $version->mappings->first()->id);
    }

    private function seedOrderType(string $suffix, string $contentPath): OrderType
    {
        OrderCategory::query()->firstOrCreate([
            'id' => 9900,
        ], [
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        $orderId = match ($suffix) {
            'StrictMissingVersion' => 9901,
            'StrictNoMappings' => 9902,
            default => 9903,
        };

        app(\App\Services\Orders\TemplateAdminService::class)->create([
            'id' => $orderId,
            'order_category_id' => 9900,
            'name' => "Order {$suffix}",
            'content' => $contentPath,
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        return OrderType::query()->create([
            'order_id' => $orderId,
            'name' => "Type {$suffix}",
        ]);
    }
}
