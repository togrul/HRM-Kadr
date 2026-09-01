<?php

use App\Models\Structure;
use App\Models\User;
use App\Modules\SidebarStructure\Livewire\Sidebar;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function seedTree(): array
{
    DB::table('structures')->insert([
        ['id' => 1, 'name' => 'Rəhbərlik', 'shortname' => 'R', 'parent_id' => null, 'code' => 1, 'level' => 0],
        ['id' => 2, 'name' => 'Maliyyə şöbəsi', 'shortname' => 'M', 'parent_id' => 1, 'code' => 2, 'level' => 1],
        ['id' => 3, 'name' => 'Uçot bölməsi', 'shortname' => 'U', 'parent_id' => 2, 'code' => 3, 'level' => 2],
    ]);

    return [1, 2, 3];
}

it('keeps the clicked node highlighted after selection', function (): void {
    seedTree();
    $this->actingAs(User::factory()->create());

    Livewire::test(Sidebar::class)
        ->call('selectStructure', 2)
        ->assertSet('selectedStructure', 2);
});

it('restores the highlight from a nested structure query string', function (): void {
    seedTree();
    $this->actingAs(User::factory()->create());

    // What AllPersonnel writes to the URL: the clicked node plus every descendant.
    $nested = Structure::withRecursive('subs')->find(2)->getAllNestedIds();

    Livewire::withQueryParams(['structure' => $nested])
        ->test(Sidebar::class)
        ->assertSet('selectedStructure', 2);
});

it('renders the selected marker on the clicked node', function (): void {
    seedTree();
    $this->actingAs(User::factory()->create());

    Livewire::test(Sidebar::class)
        ->call('selectStructure', 2)
        ->assertSeeHtml('aria-current="true"');
});

it('keeps the highlight on a real page load carrying the nested structure filter', function (): void {
    seedTree();

    $user = User::factory()->create();
    foreach (['show-personnels'] as $permission) {
        \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo('show-personnels');
    $this->actingAs($user);

    $nested = Structure::withRecursive('subs')->find(2)->getAllNestedIds();

    $this->get(route('personnel.index', ['structure' => $nested]))
        ->assertOk()
        ->assertSee('aria-current="true"', false);
});

it('recovers the highlight when the panel is re-mounted mid-request', function (): void {
    seedTree();
    $this->actingAs(User::factory()->create());

    // A re-mount (parent re-render / teleported panel) has no page query string to read,
    // which is exactly when the highlight used to vanish.
    Livewire::test(Sidebar::class, ['selected' => 2])
        ->assertSet('selectedStructure', 2)
        ->assertSeeHtml('aria-current="true"');
});

it('takes no selection when the host has no structure filter', function (): void {
    seedTree();
    $this->actingAs(User::factory()->create());

    Livewire::test(Sidebar::class)
        ->assertSet('selectedStructure', null)
        ->assertDontSeeHtml('aria-current="true"');
});

it('hands the clicked unit to the host as the head of the nested filter', function (): void {
    seedTree();

    $nested = Structure::withRecursive('subs')->find(2)->getAllNestedIds();

    // The host highlights $structure[0]; that contract only holds while the clicked unit
    // stays first in the descendant list.
    expect($nested[0])->toBe(2)
        ->and($nested)->toContain(3);
});
