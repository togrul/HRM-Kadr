<?php

use App\Models\Position;
use App\Models\User;
use App\Modules\Admin\Livewire\Positions;
use App\Support\VtiskCategory;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * Admin → Vəzifələr: vəzifəyə VTİSK kateqoriyası təyin edilir (ƏM m.114.3 «b» — rəhbər və
 * mütəxəssis 30 gün) və kateqoriyası təyin olunmamış vəzifələr ayrıca süzülür.
 */

function pvAdmin(): User
{
    return grantAllStructures(User::factory()->create())->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));
}

it('assigns a VTİSK category to a position', function (): void {
    $position = Position::query()->create(['id' => 9301, 'name' => 'Baş mühasib']);

    Livewire::actingAs(pvAdmin())->test(Positions::class)
        ->call('openCrud', $position->id)
        ->set('form.vtisk_category', VtiskCategory::SPECIALIST)
        ->call('store')
        ->assertHasNoErrors();

    expect($position->fresh()->vtisk_category)->toBe(VtiskCategory::SPECIALIST);
});

it('lists only the positions without a VTİSK category when filtered', function (): void {
    Position::query()->create(['id' => 9302, 'name' => 'Kateqoriyalı vəzifə', 'vtisk_category' => VtiskCategory::MANAGER]);
    Position::query()->create(['id' => 9303, 'name' => 'Kateqoriyasız vəzifə']);

    Livewire::actingAs(pvAdmin())->test(Positions::class)
        ->assertSee('Kateqoriyalı vəzifə')
        ->assertSee(__('admin::references.messages.vtisk_missing'))
        ->set('onlyMissingVtisk', true)
        ->assertSee('Kateqoriyasız vəzifə')
        ->assertDontSee('Kateqoriyalı vəzifə')
        ->assertViewHas('missingVtisk', Position::query()->whereNull('vtisk_category')->count());
});
