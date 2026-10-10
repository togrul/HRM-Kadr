<?php

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * H1: `is_pending` qoruma istisnasının əsasıdır. Forma onu qəbul etməməli, model səviyyəsində
 * isə təsdiqlənmiş əməkdaş yenidən "gözləyən" edilə bilməməlidir — əks halda əmrlə dəyişmə
 * qaydası iki addımda (əvvəl bayraq, sonra sahə) keçilir.
 */

function integrityPendingUser(): User
{
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');
    $user = User::factory()->create();
    foreach (['add-personnels', 'edit-personnels'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return grantAllStructures($user);
}

it('ignores a crafted is_pending in the edit form and keeps order-only fields locked', function (): void {
    $user = integrityPendingUser();
    $personnel = app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user)->fresh();
    expect((bool) $personnel->is_pending)->toBeFalse();
    Livewire::actingAs($user);

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->set('personalForm.personnel.is_pending', true)
        ->set('personalForm.personnelExtra.is_pending', true)
        ->call('store');

    expect((bool) $personnel->fresh()->is_pending)->toBeFalse();

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->set('personalForm.personnel.surname', 'Saxtayev')
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.surname']);

    expect($personnel->fresh()->surname)->toBe($personnel->surname);
});

it('refuses to flip an approved employee back to pending at the model layer', function (): void {
    $user = integrityPendingUser();
    $personnel = app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user)->fresh();

    expect(fn () => $personnel->fresh()->update(['is_pending' => true]))->toThrow(ValidationException::class)
        ->and((bool) $personnel->fresh()->is_pending)->toBeFalse();
});

it('still exempts a genuinely pending hire', function (): void {
    $user = integrityPendingUser();
    $personnel = app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user)->fresh();
    Personnel::withoutEvents(fn () => $personnel->forceFill(['is_pending' => true])->save());

    $personnel->fresh()->update(['surname' => 'Gözləyən']);

    expect($personnel->fresh()->surname)->toBe('Gözləyən');
});
