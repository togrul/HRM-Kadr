<?php

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
