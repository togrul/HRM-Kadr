<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderTemplateField;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Services\Orders\OrderTemplateFormSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTemplateFormSchemaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_metadata_required_when_no_metadata_version_exists(): void
    {
        $orderType = $this->seedOrderType('NoMetadata');

        $result = app(OrderTemplateFormSchemaService::class)->resolveForOrderType((int) $orderType->id);

        $this->assertSame('metadata_required', $result['source']);
        $this->assertSame([], $result['row_field_keys']);
        $this->assertSame([], $result['field_catalog']);
    }

    public function test_it_returns_metadata_required_when_active_version_has_no_row_mappings(): void
    {
        $orderType = $this->seedOrderType('NoMappings');
        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Set',
        ]);

        OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_path' => 'templates/no-mappings.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $result = app(OrderTemplateFormSchemaService::class)->resolveForOrderType((int) $orderType->id);

        $this->assertSame('metadata_required', $result['source']);
        $this->assertSame([], $result['row_field_keys']);
        $this->assertSame([], $result['field_catalog']);
    }

    public function test_it_uses_metadata_row_fields_and_ui_config_when_available(): void
    {
        $orderType = $this->seedOrderType('Metadata');
        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Set',
        ]);

        $version = OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_path' => 'templates/order-v1.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$fullname',
            'label' => 'Select personnel',
            'field_type' => 'select',
            'sort_order' => 10,
            'ui_config' => [
                'field' => 'personnel_id',
                'model' => '_personnels',
                'searchField' => 'search.personnel',
                'group' => 'personnel',
                'group_title' => 'Personnel',
                'group_order' => 1,
                'field_order' => 10,
                'grid_cols' => ['default' => 1, 'sm' => 2],
                'col_span' => ['default' => 1, 'sm' => 2],
            ],
        ]);

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$day',
            'label' => 'Day',
            'field_type' => 'number',
            'sort_order' => 20,
            'ui_config' => [
                'group' => 'timing',
                'group_title' => 'Timing',
                'group_order' => 2,
                'field_order' => 20,
            ],
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '${fullname}',
            'field_key' => '$fullname',
            'scope' => 'row',
            'sort_order' => 10,
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '${day}',
            'field_key' => '$day',
            'scope' => 'row',
            'sort_order' => 20,
        ]);

        $result = app(OrderTemplateFormSchemaService::class)->resolveForOrderType((int) $orderType->id);

        $this->assertSame('metadata', $result['source']);
        $this->assertSame(['$fullname', '$day'], $result['row_field_keys']);
        $this->assertSame('personnel_id', $result['field_catalog']['$fullname']['field']);
        $this->assertSame('Əməkdaş seçin', $result['field_catalog']['$fullname']['title']);
        $this->assertSame('_personnels', $result['field_catalog']['$fullname']['model']);
        $this->assertSame('select', $result['field_catalog']['$fullname']['input']);
        $this->assertSame('nullable|int', $result['field_catalog']['$fullname']['rules']);
        $this->assertSame('personnel', $result['field_catalog']['$fullname']['group']);
        $this->assertSame(['default' => 1, 'sm' => 2], $result['field_catalog']['$fullname']['grid_cols']);
        $this->assertSame(['default' => 1, 'sm' => 2], $result['field_catalog']['$fullname']['col_span']);
        $this->assertSame('day', $result['field_catalog']['$day']['field']);
        $this->assertSame('Gün', $result['field_catalog']['$day']['title']);
        $this->assertSame('numeric-input', $result['field_catalog']['$day']['input']);
        $this->assertSame('nullable|numeric', $result['field_catalog']['$day']['rules']);
        $this->assertSame('timing', $result['field_catalog']['$day']['group']);
        $this->assertContains('personnel_id', $result['dropdown_fields']);
        $this->assertCount(2, $result['row_groups']);
        $this->assertSame('personnel', $result['row_groups'][0]['key']);
        $this->assertSame('Əməkdaş məlumatları', $result['row_groups'][0]['title']);
        $this->assertSame(['$fullname'], $result['row_groups'][0]['fields']);
        $this->assertSame('timing', $result['row_groups'][1]['key']);
        $this->assertSame('Tarix məlumatları', $result['row_groups'][1]['title']);
        $this->assertSame(['$day'], $result['row_groups'][1]['fields']);
    }

    public function test_it_translates_only_canonical_namespaced_stored_titles(): void
    {
        app()->setLocale('az');

        $orderType = $this->seedOrderType('TranslatedTitles');
        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Set',
        ]);

        $version = OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_path' => 'templates/order-translated.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
            'meta' => [
                'form' => [
                    'section_blocks' => [
                        [
                            'key' => 'row_fields',
                            'title' => 'orders::template_set_type.labels.section_blocks',
                            'enabled' => true,
                            'order' => 10,
                        ],
                    ],
                ],
            ],
        ]);

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$fullname',
            'label' => 'orders::template_metadata_defaults.fields.select_personnel',
            'field_type' => 'select',
            'sort_order' => 10,
            'ui_config' => [
                'field' => 'personnel_id',
                'model' => '_personnels',
                'group' => 'personnel',
                'group_title' => 'orders::template_set_type.labels.group_title',
                'group_order' => 1,
                'field_order' => 10,
            ],
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '${fullname}',
            'field_key' => '$fullname',
            'scope' => 'row',
            'sort_order' => 10,
        ]);

        $result = app(OrderTemplateFormSchemaService::class)->resolveForOrderType((int) $orderType->id);

        $this->assertSame('Əməkdaş seçin', $result['field_catalog']['$fullname']['title']);
        $this->assertSame('Qrup başlığı', $result['row_groups'][0]['title']);
        $this->assertSame('Bölmə blokları', $result['section_blocks'][0]['title']);
    }

    public function test_it_applies_canonical_lookup_defaults_when_metadata_ui_config_is_incomplete(): void
    {
        app()->setLocale('az');

        $orderType = $this->seedOrderType('IncompleteUiConfig');
        $set = OrderTemplateSet::query()->create([
            'order_type_id' => $orderType->id,
            'name' => 'Set',
        ]);

        $version = OrderTemplateVersion::query()->create([
            'order_template_set_id' => $set->id,
            'version_no' => 1,
            'template_path' => 'templates/incomplete-ui-config.docx',
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        foreach ([
            '$rank' => 'Rank',
            '$fullname' => 'Fullname',
            '$structure_main' => 'Structure Main',
            '$structure' => 'Structure',
            '$position' => 'Position',
            '$day' => 'Day',
            '$month' => 'Month',
        ] as $fieldKey => $label) {
            OrderTemplateField::query()->create([
                'order_template_version_id' => $version->id,
                'field_key' => $fieldKey,
                'label' => $label,
                'field_type' => 'text',
                'sort_order' => 10,
                'ui_config' => [],
            ]);

            OrderTemplateMapping::query()->create([
                'order_template_version_id' => $version->id,
                'placeholder' => '{'.ltrim($fieldKey, '$').'}',
                'field_key' => $fieldKey,
                'scope' => 'row',
                'sort_order' => 10,
            ]);
        }

        $result = app(OrderTemplateFormSchemaService::class)->resolveForOrderType((int) $orderType->id);

        $this->assertSame('rank_id', $result['field_catalog']['$rank']['field']);
        $this->assertSame('Rütbə seçin', $result['field_catalog']['$rank']['title']);
        $this->assertSame('_ranks', $result['field_catalog']['$rank']['model']);
        $this->assertSame('select', $result['field_catalog']['$rank']['input']);

        $this->assertSame('personnel_id', $result['field_catalog']['$fullname']['field']);
        $this->assertSame('Əməkdaş seçin', $result['field_catalog']['$fullname']['title']);
        $this->assertSame('_personnels', $result['field_catalog']['$fullname']['model']);

        $this->assertSame('structure_main_id', $result['field_catalog']['$structure_main']['field']);
        $this->assertSame('Əsas struktur seçin', $result['field_catalog']['$structure_main']['title']);
        $this->assertSame('_main_structures', $result['field_catalog']['$structure_main']['model']);

        $this->assertSame('structure_id', $result['field_catalog']['$structure']['field']);
        $this->assertSame('Struktur seçin', $result['field_catalog']['$structure']['title']);
        $this->assertSame('_structures', $result['field_catalog']['$structure']['model']);
        $this->assertSame('radio-list', $result['field_catalog']['$structure']['input']);

        $this->assertSame('position_id', $result['field_catalog']['$position']['field']);
        $this->assertSame('Vəzifə seçin', $result['field_catalog']['$position']['title']);
        $this->assertSame('_positions', $result['field_catalog']['$position']['model']);

        $this->assertSame('Gün', $result['field_catalog']['$day']['title']);
        $this->assertSame('numeric-input', $result['field_catalog']['$day']['input']);
        $this->assertSame('Ay', $result['field_catalog']['$month']['title']);

        $this->assertContains('rank_id', $result['dropdown_fields']);
        $this->assertContains('personnel_id', $result['dropdown_fields']);
        $this->assertContains('structure_main_id', $result['dropdown_fields']);
        $this->assertContains('structure_id', $result['dropdown_fields']);
        $this->assertContains('position_id', $result['dropdown_fields']);
    }

    private function seedOrderType(string $suffix): OrderType
    {
        OrderCategory::query()->firstOrCreate([
            'id' => 9700,
        ], [
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        $orderId = match ($suffix) {
            'NoMetadata' => 9701,
            'NoMappings' => 9702,
            'TranslatedTitles' => 9704,
            default => 9703,
        };
        app(\App\Services\Orders\TemplateAdminService::class)->create([
            'id' => $orderId,
            'order_category_id' => 9700,
            'name' => "Order {$suffix}",
            'content' => "templates/{$suffix}.docx",
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        return OrderType::query()->create([
            'order_id' => $orderId,
            'name' => "Type {$suffix}",
        ]);
    }
}
