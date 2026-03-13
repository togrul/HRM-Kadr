<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderType;
use App\Models\User;
use App\Modules\Orders\Livewire\AddOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AddOrderOptionGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_order_loads_template_options_only_after_requested_group_is_opened(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-orders', 'web'));

        $category = new OrderCategory;
        $category->forceFill([
            'id' => 1,
            'name_az' => 'Orders',
        ])->save();

        $order = Order::query()->create([
            'order_category_id' => $category->id,
            'name' => 'Personnel order',
            'content' => 'Template body',
            'order_model' => \App\Models\Personnel::class,
            'blade' => Order::BLADE_DEFAULT,
        ]);

        OrderType::query()->create([
            'order_id' => $order->id,
            'name' => 'Initial appointment',
        ]);

        $this->actingAs($user);

        Livewire::test(AddOrder::class)
            ->assertDontSee('Initial appointment')
            ->call('loadOptionGroup', 'templates')
            ->assertSee('Initial appointment');
    }

    public function test_add_order_persists_rank_and_main_structure_search_state(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-orders', 'web'));

        $this->actingAs($user);

        Livewire::test(AddOrder::class)
            ->set('search.rank', 'gen')
            ->set('search.mainStructure', 'hq')
            ->assertSet('search.rank', 'gen')
            ->assertSet('search.mainStructure', 'hq');
    }
}
