<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Livewire\AllOrders;
use App\Modules\Orders\Livewire\OrderPreview;
use App\Services\StructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AllOrdersInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_orders_can_rerender_after_status_and_search_updates(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-orders', 'web'));

        $this->actingAs($user);

        Livewire::test(AllOrders::class)
            ->call('setStatus', 'all')
            ->set('search.order_no', '0908')
            ->assertSet('search.order_no', '0908');
    }

    public function test_pending_hire_order_is_visible_via_snapshot_structure(): void
    {
        foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
            OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
        }

        $structureId = 7;
        // A pending hire order has NO personnel yet, so it can only be scoped by the
        // target structure in its snapshot — grant the viewer exactly that structure.
        $this->app->instance(StructureService::class, new class($structureId) extends StructureService
        {
            public function __construct(private int $id) {}

            public function getAccessibleStructures(?User $user = null): array
            {
                return [$this->id];
            }
        });

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-orders', 'web'));
        $this->actingAs($user);

        OrderLog::query()->create([
            'order_id' => null,
            'order_no' => 'IQ-VIS-1',
            'given_date' => now(),
            'given_by' => 'Test',
            'given_by_rank' => '',
            'status_id' => 10,
            'creator_id' => $user->id,
            'template_render_mode' => 'docx_v1',
            'template_snapshot' => [
                'template_code' => 'ise_qebul',
                'candidate_id' => 1,
                'hire_structure_id' => $structureId,
                'hire_position_id' => 2,
            ],
        ]);

        Livewire::test(AllOrders::class)
            ->call('setStatus', 'all')
            ->assertSee('IQ-VIS-1');
    }

    public function test_contextual_panel_renders_inside_the_component_root(): void
    {
        foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
            OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
        }

        $category = OrderCategory::query()->create(['id' => 1, 'name_az' => 'Kadr', 'name_en' => 'HR', 'name_ru' => 'HR']);
        Order::query()->forceCreate(['id' => 1010, 'order_category_id' => $category->id, 'name' => 'İşə qəbul', 'content' => '', 'order_model' => '', 'blade' => Order::BLADE_DEFAULT]);

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-orders', 'web'));
        $this->actingAs($user);

        // Order::IG_EMR is globally visible, so this row needs no structure grant.
        OrderLog::query()->create([
            'order_id' => 1010,
            'order_no' => 'LEGACY-1',
            'given_date' => now(),
            'given_by' => 'Test',
            'given_by_rank' => '',
            'status_id' => 20,
            'creator_id' => $user->id,
        ]);

        // The status / type selects live in the component's own header toolbar, so their
        // wire:model bindings reach the component.
        Livewire::test(AllOrders::class)
            ->assertSeeHtml('orders-status-filter')
            ->assertSeeHtml('orders-type-filter')
            ->assertSee('İşə qəbul')
            ->assertSee('Təsdiq gözləyən')
            ->set('status', 20)
            ->assertSet('status', 20)
            ->set('status', 'deleted') // admin only
            ->assertSet('status', 'all')
            ->set('selectedOrder', 1010)
            ->assertSet('selectedOrder', 1010)
            ->set('selectedOrder', null)
            ->assertSet('selectedOrder', null);
    }

    public function test_excel_export_streams_a_file_for_a_permitted_user(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-orders', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('export-orders', 'web'));
        $this->actingAs($user);

        $this->freezeTime();
        Excel::fake();

        Livewire::test(AllOrders::class)->call('exportExcel');

        Excel::assertDownloaded('orders-'.now()->format('d.m.Y H:i').'.xlsx');
    }

    public function test_each_row_offers_one_status_driven_primary_action(): void
    {
        $user = $this->actAsOrderManager();

        $this->docxOrder('DRAFT-1', 10, null, $user);
        $this->docxOrder('READY-1', 10, 'order-documents/1.docx', $user);
        $this->docxOrder('DONE-1', 20, 'order-documents/2.docx', $user);

        Livewire::test(AllOrders::class)
            ->assertSee(__('orders::order_list.status.draft'))
            ->assertSee(__('orders::order_list.actions.continue'))
            ->assertSee("\$wire.approveOrder('READY-1')")
            ->assertSee("printOrder('DONE-1')", false)
            ->assertSee(__('orders::order_list.actions.duplicate'))
            ->assertSee("\$wire.deleteOrder('DONE-1')")
            ->assertSee("openSideMenu('order-preview'", false)
            ->assertDontSee('wire:confirm', false);
    }

    public function test_download_needs_the_same_export_permission_that_shows_the_button(): void
    {
        $user = $this->actAsOrderManager();
        $this->docxOrder('DL-1', 20, 'order-documents/dl.docx', $user);
        Storage::fake('local')->put('order-documents/dl.docx', 'docx');

        Livewire::test(AllOrders::class)->call('printOrder', 'DL-1')->assertFileDownloaded('DL-1.docx');

        $user->revokePermissionTo('export-orders');
        Livewire::test(AllOrders::class)
            ->assertDontSee("printOrder('DL-1')", false)
            ->call('printOrder', 'DL-1')
            ->assertForbidden();

        $exportOnly = User::factory()->create();
        $exportOnly->givePermissionTo(['show-orders', 'export-orders']);
        $this->actingAs($exportOnly);
        Livewire::test(AllOrders::class)->call('printOrder', 'DL-1')->assertFileDownloaded('DL-1.docx');
    }

    public function test_a_draft_cannot_be_approved_from_the_list(): void
    {
        $user = $this->actAsOrderManager();
        $draft = $this->docxOrder('DRAFT-2', 10, null, $user);

        Livewire::test(AllOrders::class)
            ->call('approveOrder', 'DRAFT-2')
            ->assertDispatched('orderError');

        $this->assertSame(10, (int) $draft->fresh()->status_id);
    }

    public function test_duplicate_copies_an_order_as_a_new_draft(): void
    {
        $user = $this->actAsOrderManager();
        $source = $this->docxOrder('214-M', 20, 'order-documents/9.docx', $user, ['var_2' => '19.05.2026-cı il']);

        Livewire::test(AllOrders::class)
            ->call('duplicateOrder', '214-M')
            ->assertDispatched('orderAdded')
            ->call('duplicateOrder', '214-M');

        $copy = OrderLog::where('order_no', '214-M-kopya')->firstOrFail();
        $this->assertSame(OrderIssueService::STATUS_PENDING, (int) $copy->status_id);
        $this->assertTrue(OrderIssueService::isDraft($copy));
        $this->assertSame('ise_qebul', data_get($copy->template_snapshot, 'template_code'));
        $this->assertSame(['var_2' => '19.05.2026-cı il'], data_get($copy->template_snapshot, 'fields'));
        $this->assertSame(7, data_get($copy->template_snapshot, 'hire_structure_id'));
        $this->assertTrue(OrderLog::where('order_no', '214-M-kopya-2')->exists());
        $this->assertSame(20, (int) $source->fresh()->status_id);
    }

    public function test_delete_soft_deletes_the_order(): void
    {
        $user = $this->actAsOrderManager();
        $order = $this->docxOrder('DEL-1', 10, null, $user);

        Livewire::test(AllOrders::class)
            ->call('deleteOrder', 'DEL-1')
            ->assertDispatched('orderWasDeleted');

        $this->assertSoftDeleted($order);
    }

    public function test_the_row_preview_opens_in_the_side_panel(): void
    {
        $user = $this->actAsOrderManager();
        $order = $this->docxOrder('PRV-1', 10, null, $user);

        Livewire::test(AllOrders::class)
            ->call('openSideMenu', 'order-preview', $order->id)
            ->assertSeeLivewire(OrderPreview::class);

        Livewire::test(OrderPreview::class, ['orderId' => $order->id])
            ->assertSee('PRV-1')
            ->assertSee(__('orders::order_list.preview.no_document'));
    }

    private function actAsOrderManager(): User
    {
        foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
            OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
        }

        $this->app->instance(StructureService::class, new class extends StructureService
        {
            public function __construct() {}

            public function getAccessibleStructures(?User $user = null): array
            {
                return [7];
            }
        });

        $user = User::factory()->create();
        foreach (['show-orders', 'add-orders', 'export-orders', 'delete-orders'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);

        return $user;
    }

    /**
     * @param  array<string,string>  $fields
     */
    private function docxOrder(string $no, int $status, ?string $docxPath, User $user, array $fields = []): OrderLog
    {
        return OrderLog::query()->create([
            'order_id' => null,
            'order_no' => $no,
            'given_date' => now(),
            'given_by' => 'Test',
            'given_by_rank' => '',
            'status_id' => $status,
            'creator_id' => $user->id,
            'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
            'template_snapshot' => [
                'template_code' => 'ise_qebul',
                'label' => 'İşə qəbul',
                'fields' => $fields,
                'candidate_id' => 1,
                'hire_structure_id' => 7,
                'hire_position_id' => 2,
                'order_date_text' => '14.05.2026-cı il',
                'docx_path' => $docxPath,
            ],
        ]);
    }
}
