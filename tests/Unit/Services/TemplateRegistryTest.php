<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Services\Orders\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_active_version_with_fields_and_mappings(): void
    {
        $orderType = $this->makeOrderType('legacy/path.docx');
        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Default set',
        ]);

        OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_path' => 'legacy/v1.docx',
            'status' => 'archived',
            'is_active' => false,
        ]);

        $active = OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 2,
            'template_path' => 'legacy/v2.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        OrderTemplateField::query()->create([
            'order_template_version_id' => $active->id,
            'field_key' => '$fullname',
            'label' => 'Tam ad',
            'field_type' => 'text',
            'sort_order' => 10,
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $active->id,
            'placeholder' => 'content_text',
            'field_key' => '$fullname',
            'scope' => 'row',
            'sort_order' => 10,
        ]);

        $resolved = app(TemplateRegistry::class)->activeVersionForOrderType((int) $orderType->id);

        $this->assertNotNull($resolved);
        $this->assertSame($active->id, $resolved->id);
        $this->assertCount(1, $resolved->fields);
        $this->assertCount(1, $resolved->mappings);
    }

    public function test_it_invalidates_cached_active_version(): void
    {
        $orderType = $this->makeOrderType('legacy/path.docx');
        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Set',
        ]);

        $v1 = OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_path' => 'templates/v1.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $service = app(TemplateRegistry::class);
        $this->assertSame('templates/v1.docx', $service->resolveTemplatePathForOrderType((int) $orderType->id));

        $v1->update(['is_active' => false, 'status' => 'archived']);
        OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 2,
            'template_path' => 'templates/v2.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $service->invalidate((int) $orderType->id);

        $this->assertSame('templates/v2.docx', $service->resolveTemplatePathForOrderType((int) $orderType->id));
    }

    public function test_it_falls_back_to_order_content_when_no_active_version_exists(): void
    {
        $orderType = $this->makeOrderType('templates/legacy.docx');

        $path = app(TemplateRegistry::class)->resolveTemplatePathForOrderType((int) $orderType->id);

        $this->assertSame('templates/legacy.docx', $path);
    }

    private function makeOrderType(string $contentPath): OrderType
    {
        OrderCategory::query()->create([
            'id' => 9001,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        Order::query()->create([
            'id' => 9101,
            'order_category_id' => 9001,
            'name' => 'Template',
            'content' => $contentPath,
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        return OrderType::query()->create([
            'order_id' => 9101,
            'name' => 'Type A',
        ]);
    }
}
