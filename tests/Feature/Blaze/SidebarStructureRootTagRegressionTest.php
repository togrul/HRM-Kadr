<?php

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\Structure;
use App\Models\User;
use Livewire\Livewire;

dataset('sidebar-structure-components', [
    'structure.sidebar' => [
        'structure.sidebar',
        function (): void {
            Structure::query()->create([
                'id' => 990001,
                'parent_id' => null,
                'name' => 'Smoke Root Structure',
                'shortname' => 'Smoke Root',
                'code' => 990001,
                'level' => 0,
            ]);
        },
    ],
    'structure.orders' => [
        'structure.orders',
        function (): void {
            OrderCategory::query()->create([
                'id' => 990001,
                'name_az' => 'Smoke Category',
                'name_en' => 'Smoke Category',
                'name_ru' => 'Smoke Category',
            ]);

            Order::query()->insert([
                'id' => 990001,
                'order_category_id' => 990001,
                'name' => 'Smoke Order',
                'content' => 'Smoke content',
                'order_model' => '\\App\\Models\\Personnel',
                'blade' => Order::BLADE_DEFAULT,
            ]);
        },
    ],
    'structure.services' => ['structure.services', function (): void {}],
]);

it('mounts sidebar structure livewire components without root-tag regressions', function (string $alias, Closure $seed) {
    $this->actingAs(User::factory()->create());
    $seed();

    $component = Livewire::test($alias);
    $html = trim($component->html());

    expect($html)->not()->toBe('');
    expect($html)->toContain('<div');
})->with('sidebar-structure-components');
