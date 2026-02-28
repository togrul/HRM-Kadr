<?php

namespace Tests\Feature\Console;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderTemplateQueryBudgetCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_skip_when_reference_order_is_missing_and_allow_empty_enabled(): void
    {
        $exitCode = Artisan::call('orders:templates:query-budget', [
            '--json' => true,
            '--allow-empty' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertTrue((bool) data_get($payload, 'summary.skipped'));
        $this->assertSame('reference_order_not_found', data_get($payload, 'summary.reason'));
        $this->assertCount(0, data_get($payload, 'results', []));
    }

    public function test_it_reports_add_edit_print_query_budgets(): void
    {
        [$orderType, $orderLog] = $this->seedOrderForBudgetProbe();

        $exitCode = Artisan::call('orders:templates:query-budget', [
            '--order-type' => $orderType->id,
            '--order-no' => $orderLog->order_no,
            '--add-budget' => 200,
            '--edit-budget' => 200,
            '--print-budget' => 200,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertSame(3, data_get($payload, 'summary.passed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertCount(3, data_get($payload, 'results'));
        $this->assertSame('add_form_schema', data_get($payload, 'results.0.flow'));
        $this->assertSame('edit_order_load', data_get($payload, 'results.1.flow'));
        $this->assertSame('print_payload_build', data_get($payload, 'results.2.flow'));
        $this->assertSame('ok', data_get($payload, 'results.2.status'));
    }

    /**
     * @return array{0: \App\Models\OrderType, 1: \App\Models\OrderLog}
     */
    private function seedOrderForBudgetProbe(): array
    {
        OrderCategory::query()->create([
            'id' => 9500,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        $order = Order::query()->create([
            'id' => 9501,
            'order_category_id' => 9500,
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

        OrderTemplateField::query()->create([
            'order_template_version_id' => $version->id,
            'field_key' => '$fullname',
            'label' => 'Tam ad',
            'field_type' => 'text',
            'sort_order' => 10,
            'ui_config' => [
                'field' => 'fullname',
                'input' => 'select',
                'model' => \App\Models\Personnel::class,
                'selectedName' => 'selectedPersonnelName',
                'searchField' => 'search.selectedPersonnelName',
            ],
        ]);

        OrderTemplateMapping::query()->create([
            'order_template_version_id' => $version->id,
            'placeholder' => '$fullname',
            'field_key' => '$fullname',
            'scope' => 'row',
            'sort_order' => 10,
        ]);

        OrderStatus::query()->create([
            'id' => 10,
            'locale' => 'az',
            'name' => 'Gözləmədə',
        ]);

        $user = User::factory()->create();

        $orderLog = OrderLog::query()->create([
            'order_id' => $order->id,
            'order_type_id' => $orderType->id,
            'order_no' => 'QB-001',
            'given_date' => '2026-02-25 00:00:00',
            'given_by' => 'Ferid Əsgərov',
            'given_by_rank' => 'general-mayor',
            'status_id' => 10,
            'creator_id' => $user->id,
        ]);

        $componentId = (int) DB::table('components')->insertGetId([
            'order_id' => $order->id,
            'name' => 'İşə qəbul',
            'content' => '$fullname',
            'dynamic_fields' => json_encode(['$fullname'], JSON_UNESCAPED_UNICODE),
        ]);

        DB::table('order_log_components')->insert([
            'order_no' => $orderLog->order_no,
            'component_id' => $componentId,
            'row_number' => 0,
        ]);

        OrderLogComponentAttributes::query()->create([
            'order_no' => $orderLog->order_no,
            'component_id' => $componentId,
            'row_number' => 0,
            'attributes' => [
                '$fullname' => ['value' => 'Test User', 'id' => null],
            ],
        ]);

        return [$orderType, $orderLog];
    }
}
