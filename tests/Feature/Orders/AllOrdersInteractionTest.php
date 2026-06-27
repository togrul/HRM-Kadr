<?php

namespace Tests\Feature\Orders;

use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Models\User;
use App\Modules\Orders\Livewire\AllOrders;
use App\Services\StructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
}
