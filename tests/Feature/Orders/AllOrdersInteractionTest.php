<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Modules\Orders\Livewire\AllOrders;
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
}
