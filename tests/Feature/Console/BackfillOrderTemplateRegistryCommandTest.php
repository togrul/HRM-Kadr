<?php

namespace Tests\Feature\Console;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillOrderTemplateRegistryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_template_set_and_active_version(): void
    {
        $orderType = $this->makeOrderType('templates/order-default.docx');

        $this->artisan('orders:templates:backfill')
            ->assertSuccessful();

        $this->assertDatabaseHas('order_template_sets', [
            'order_type_id' => $orderType->id,
        ]);

        $this->assertDatabaseHas('order_template_versions', [
            'template_path' => 'templates/order-default.docx',
            'version_no' => 1,
            'is_active' => 1,
            'status' => 'published',
        ]);
    }

    public function test_it_is_idempotent_when_active_version_matches_template_path(): void
    {
        $this->makeOrderType('templates/order-default.docx');

        $this->artisan('orders:templates:backfill')->assertSuccessful();
        $this->artisan('orders:templates:backfill')->assertSuccessful();

        $this->assertSame(1, \App\Models\OrderTemplateVersion::query()->count());
    }

    private function makeOrderType(string $contentPath): OrderType
    {
        OrderCategory::query()->create([
            'id' => 9100,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        Order::query()->create([
            'id' => 9200,
            'order_category_id' => 9100,
            'name' => 'İşə qəbul',
            'content' => $contentPath,
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        return OrderType::query()->create([
            'order_id' => 9200,
            'name' => 'İşə qəbul',
        ]);
    }
}
