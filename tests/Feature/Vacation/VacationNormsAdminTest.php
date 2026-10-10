<?php

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\User;
use App\Models\VacationBalanceEntry;
use App\Models\VacationNorm;
use App\Modules\Personnel\Livewire\VacationList;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Modules\Vacation\Livewire\VacationNorms;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * Admin → Məzuniyyət normaları və işçi kartında iş illəri / açılış qalığı.
 */

function vaAdmin(): User
{
    return grantAllStructures(User::factory()->create())->givePermissionTo(
        Permission::findOrCreate('access-admin', 'web'),
        Permission::findOrCreate('edit-personnels', 'web'),
    );
}

function vaPersonnel(): Personnel
{
    $structure = Structure::query()->create(['name' => 'Keşlə', 'shortname' => 'K']);
    $position = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'qaynaqçı']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'VA'.Str::upper(Str::random(6)),
        'surname' => 'Quliyev',
        'name' => 'Nicat',
        'patronymic' => 'Elman',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'email' => Str::lower(Str::random(8)).'@example.com',
        'mobile' => '994501112233',
        'nationality_id' => 1,
        'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
        'residental_address' => 'Main st',
        'education_degree_id' => 1,
        'work_norm_id' => 1,
        'structure_id' => $structure->id,
        'position_id' => $position->id,
        'join_work_date' => '2024-03-15',
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

it('opens the norms screen for admins only', function (): void {
    $this->actingAs(vaAdmin())->get(route('admin.vacation-norms'))
        ->assertOk()
        ->assertSee(__('vacation::norms.title'))
        ->assertSee('ƏM m.114.2');

    $this->actingAs(User::factory()->create())->get(route('admin.vacation-norms'))->assertForbidden();
});

it('adds a working-conditions norm per position and refuses one below the legal minimum', function (): void {
    $this->actingAs(vaAdmin());
    $personnel = vaPersonnel();

    $component = Livewire::test(VacationNorms::class)
        ->call('selectGroup', 'conditions')
        ->call('openCrud')
        ->set('form.scope', 'position')
        ->set('form.position_id', $personnel->position_id)
        ->set('form.days', 4)
        ->call('store')
        ->assertHasErrors(['form.days']);

    expect(VacationNorm::query()->where('group', 'conditions')->exists())->toBeFalse();

    $component->set('form.days', 6)->set('form.legal_basis', 'ƏM m.115.1')->call('store')->assertHasNoErrors();

    expect(VacationNorm::query()->where('group', 'conditions')->sole())
        ->toMatchArray(['scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 6]);
});

it('keeps base leave at 21 days or more and never deletes a statutory norm', function (): void {
    $this->actingAs(vaAdmin());
    $statutory = VacationNorm::query()->where('group', 'base')->where('scope', 'all')->sole();

    Livewire::test(VacationNorms::class)
        ->call('openCrud', $statutory->id)
        ->set('form.days', 20)
        ->call('store')
        ->assertHasErrors(['form.days'])
        ->call('confirmDelete', $statutory->id)
        ->assertDispatched('confirm-action')
        ->call('delete');

    expect($statutory->fresh()->days)->toBe(21);

    Livewire::test(VacationNorms::class)->call('toggle', $statutory->id);
    expect($statutory->fresh()->is_active)->toBeFalse();
});

it('shows the work years with their breakdown and lets an admin add and delete an opening balance', function (): void {
    Setting::query()->where('name', VacationSettings::LEDGER_START)->update(['value' => '2026-01-01']);
    $this->actingAs(vaAdmin());
    $personnel = vaPersonnel();

    $component = Livewire::test(VacationList::class, ['personnelModel' => $personnel->tabel_no])
        ->assertSee('15.03.2025 – 14.03.2026')
        ->assertSee(__('personnel::vacations.breakdown.base', ['days' => 21]))
        ->set('openingSequence', 1)
        ->set('openingDays', 9)
        ->set('openingNote', 'Kağız uçot')
        ->call('addOpening')
        ->assertHasNoErrors()
        ->assertSee('15.03.2024 – 14.03.2025');

    $entry = VacationBalanceEntry::query()->where('kind', 'opening')->sole();
    expect($entry->days)->toBe(9)->and($entry->note)->toBe('Kağız uçot');

    $component->call('confirmDeleteOpening', $entry->id)->assertDispatched('confirm-action')->call('deleteOpening');
    expect(VacationBalanceEntry::query()->count())->toBe(0);
});

it('does not let a non-admin add an opening balance', function (): void {
    $user = grantAllStructures(User::factory()->create())->givePermissionTo(Permission::findOrCreate('edit-personnels', 'web'));
    $this->actingAs($user);
    $personnel = vaPersonnel();

    Livewire::test(VacationList::class, ['personnelModel' => $personnel->tabel_no])
        ->assertDontSee(__('personnel::vacations.titles.opening'))
        ->set('openingSequence', 1)
        ->set('openingDays', 9)
        ->call('addOpening')
        ->assertForbidden();

    expect(VacationBalanceEntry::query()->count())->toBe(0);
});
