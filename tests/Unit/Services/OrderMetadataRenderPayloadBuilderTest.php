<?php

namespace Tests\Unit\Services;

use App\Models\Component;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderLog;
use App\Models\OrderLogComponentAttributes;
use App\Models\OrderStatus;
use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Models\User;
use App\Services\Orders\OrderMetadataRenderPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderMetadataRenderPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_scalar_and_row_payload_from_metadata_mappings(): void
    {
        $context = $this->createOrderContext();
        $templateVersion = $this->createTemplateVersionWithMappings($context['orderType']->id);
        $component = $this->createComponentForType($context['order']->id, $context['orderType']->id);

        OrderLogComponentAttributes::query()->create([
            'order_no' => $context['orderLog']->order_no,
            'component_id' => $component->id,
            'attributes' => [
                '$fullname' => ['value' => '  Nigar Nur  '],
                '$position' => ['value' => 'proqramçı'],
            ],
            'row_number' => 1,
        ]);

        $payload = app(OrderMetadataRenderPayloadBuilder::class)->build($context['orderLog'], $templateVersion);

        $this->assertSame('metadata', $payload['mode']);
        $this->assertSame($templateVersion->id, $payload['template_version_id']);
        $this->assertSame('FERID ƏSGƏROV', $payload['scalar_values']['name_director']);
        $this->assertSame('A-1001', $payload['scalar_values']['order_no']);
        $this->assertCount(1, $payload['rows']);
        $this->assertSame('Nigar Nur', $payload['rows'][0]['fullname']);
        $this->assertSame('Proqramçı', $payload['rows'][0]['position']);
        $this->assertSame('leytenant', $payload['rows'][0]['rank']);
        $this->assertSame('Nigar Nur - Proqramçı', $payload['rows'][0]['content_text']);
    }

    private function createOrderContext(): array
    {
        OrderCategory::query()->create([
            'id' => 9901,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        app(\App\Services\Orders\TemplateAdminService::class)->create([
            'id' => 9902,
            'order_category_id' => 9901,
            'name' => 'İşə qəbul',
            'content' => 'templates/default.docx',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        $orderType = OrderType::query()->create([
            'order_id' => 9902,
            'name' => 'Default',
        ]);

        OrderStatus::query()->create([
            'id' => 10,
            'locale' => 'az',
            'name' => 'Gözləmədə',
        ]);

        $user = User::factory()->create();

        $orderLog = OrderLog::query()->create([
            'order_id' => 9902,
            'order_type_id' => $orderType->id,
            'order_no' => 'A-1001',
            'given_date' => '2026-02-25 00:00:00',
            'given_by' => ' Ferid Əsgərov ',
            'given_by_rank' => 'general-mayor',
            'description' => ['$extra' => 'value'],
            'status_id' => 10,
            'creator_id' => $user->id,
        ]);

        return [
            'order' => Order::findOrFail(9902),
            'orderType' => $orderType,
            'orderLog' => $orderLog,
        ];
    }

    private function createTemplateVersionWithMappings(int $orderTypeId): OrderTemplateVersion
    {
        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderTypeId,
            'name' => 'Default set',
        ]);

        $version = OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_path' => 'templates/default-v1.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$name_director',
            'label' => 'Kim tərəfindən verilib',
            'field_type' => 'text',
            'sort_order' => 10,
        ]);

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$order_no',
            'label' => 'Əmr #',
            'field_type' => 'text',
            'sort_order' => 20,
        ]);

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$fullname',
            'label' => 'Tam ad',
            'field_type' => 'text',
            'sort_order' => 30,
            'transform_config' => [['type' => 'trim']],
        ]);

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$position',
            'label' => 'Vəzifə',
            'field_type' => 'text',
            'sort_order' => 40,
            'transform_config' => [['type' => 'title']],
        ]);

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$rank',
            'label' => 'Rütbə',
            'field_type' => 'text',
            'sort_order' => 50,
            'default_value' => 'leytenant',
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '${name_director}',
            'field_key' => '$name_director',
            'scope' => 'scalar',
            'sort_order' => 10,
            'mapping_config' => ['transform' => [['type' => 'trim'], ['type' => 'upper']]],
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '${order_no}',
            'field_key' => '$order_no',
            'scope' => 'scalar',
            'sort_order' => 20,
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '${fullname}',
            'field_key' => '$fullname',
            'scope' => 'row',
            'sort_order' => 30,
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '${position}',
            'field_key' => '$position',
            'scope' => 'row',
            'sort_order' => 40,
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '${rank}',
            'field_key' => '$rank',
            'scope' => 'row',
            'sort_order' => 50,
        ]);

        return $version->fresh(['fields', 'mappings']);
    }

    private function createComponentForType(int $orderId, int $orderTypeId): Component
    {
        $payload = [
            'name' => 'Əsas komponent',
            'title' => 'Əsas komponent',
            'content' => '$fullname - $position',
            'dynamic_fields' => [],
        ];

        if (Schema::hasColumn('components', 'order_type_id')) {
            $payload['order_type_id'] = $orderTypeId;
        } else {
            $payload['order_id'] = $orderId;
        }

        return Component::query()->forceCreate($payload);
    }
}
