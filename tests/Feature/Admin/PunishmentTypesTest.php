<?php

use App\Models\PunishmentType;
use App\Models\User;
use App\Modules\Admin\Livewire\Punishments;
use App\Modules\Admin\Livewire\PunishmentTypes;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * The punishments page mounts <livewire:admin.punishment-types> to add or edit a type,
 * but the class was lost in the move to modules — opening the panel threw.
 */

beforeEach(function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));
    $this->actingAs($user);
});

it('opens the punishment type panel on the punishments page', function (): void {
    Livewire::test(Punishments::class)
        ->call('loadChildComponent')
        ->assertSet('showChild', true)
        ->assertSeeHtml('childForm.name');
});

it('creates, renames and deletes a punishment type', function (): void {
    Livewire::test(PunishmentTypes::class)
        ->set('childForm.id', 7)
        ->set('childForm.name', 'Töhmət')
        ->call('store')
        ->assertHasNoErrors()
        ->assertDispatched('punishmentTypeUpdated');

    Livewire::test(PunishmentTypes::class, ['model' => 7])
        ->assertSet('childForm.name', 'Töhmət')
        ->set('childForm.name', 'Şiddətli töhmət')
        ->call('store')
        ->assertHasNoErrors();

    expect(PunishmentType::query()->find(7)?->name)->toBe('Şiddətli töhmət');

    Livewire::test(PunishmentTypes::class, ['model' => 7])->call('delete');

    expect(PunishmentType::query()->find(7))->toBeNull();
});
