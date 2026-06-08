<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderType;
use App\Models\Component;
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

    public function test_add_order_translates_legacy_component_dynamic_fields(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-orders', 'web'));

        $category = new OrderCategory;
        $category->forceFill([
            'id' => 2,
            'name_az' => 'Orders',
        ])->save();

        $order = Order::query()->create([
            'order_category_id' => $category->id,
            'name' => 'Personnel order',
            'content' => 'Template body',
            'order_model' => \App\Models\Personnel::class,
            'blade' => Order::BLADE_DEFAULT,
        ]);

        $orderType = OrderType::query()->create([
            'order_id' => $order->id,
            'name' => 'Next appointment',
        ]);

        $component = new Component;
        $component->forceFill([
            'order_id' => $order->id,
            'name' => 'Hiring',
            'content' => 'Hiring component',
            'dynamic_fields' => '$rank,$fullname,$day,$month,$year,$structure_main,$structure,$position',
        ])->save();

        $this->actingAs($user);

        Livewire::test(AddOrder::class)
            ->set('orderForm.order_type_id', $orderType->id)
            ->set('componentForms.0.component_id', $component->id)
            ->assertSee('Rütbə seçin')
            ->assertSee('Əməkdaş seçin')
            ->assertSee('Gün')
            ->assertSee('Ay')
            ->assertSee('İl')
            ->assertSee('Əsas struktur seçin')
            ->assertSee('Struktur seçin')
            ->assertSee('Vəzifə seçin');
    }
}
