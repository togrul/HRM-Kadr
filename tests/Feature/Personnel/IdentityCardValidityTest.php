<?php

use App\Models\PersonnelIdentityDocument;
use App\Models\User;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * The ID card (şəxsiyyət vəsiqəsi) records its expiry date like a passport: optional,
 * after the issue date, shown on the Vəsiqələr step and stored with the card.
 */

function idCardEditor(): array
{
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');
    $user = grantAllStructures(User::factory()->create());

    foreach (['add-personnels', 'edit-personnels'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    $personnel = app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);
    $countryId = (int) DB::table('countries')->where('code', 'AZ')->value('id');
    DB::table('cities')->insertOrIgnore(['id' => 901, 'country_id' => $countryId, 'parent_id' => null, 'name' => 'Bakı']);
    Livewire::actingAs($user);

    $component = Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->call('selectStep', 2)
        ->set('documentForm.document.pin', 'ABC1234')
        ->set('documentForm.document.nationality_id', $countryId)
        ->set('documentForm.document.series', 'AA')
        ->set('documentForm.document.number', '1234567')
        ->set('documentForm.document.born_country_id', $countryId)
        ->set('documentForm.document.born_city_id', 901)
        ->set('documentForm.document.is_married', false)
        ->set('documentForm.document.height', 175);

    return [$component, $personnel];
}

it('rejects an expiry date that is not after the issue date', function (): void {
    [$component, $personnel] = idCardEditor();

    $component
        ->set('documentForm.document.document_issued_date', '2026-05-10')
        ->set('documentForm.document.valid_date', '2026-05-10')
        ->call('store')
        ->assertHasErrors(['documentForm.document.valid_date' => 'after']);

    expect($component->errors()->first('documentForm.document.valid_date'))
        ->toBe(__('personnel::common.validation.id_card_valid_after_issue'));
    $this->assertDatabaseMissing('personnel_identity_documents', ['tabel_no' => $personnel->tabel_no]);
});

it('stores the expiry date with the ID card and loads it back', function (): void {
    [$component, $personnel] = idCardEditor();

    $component
        ->set('documentForm.document.document_issued_date', '2016-05-10')
        ->set('documentForm.document.valid_date', '2026-05-10')
        ->call('store')
        ->assertHasNoErrors();

    $card = PersonnelIdentityDocument::query()->where('tabel_no', $personnel->tabel_no)->sole();
    expect($card->getRawOriginal('valid_date'))->toStartWith('2026-05-10');

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->call('selectStep', 2)
        ->assertSet('documentForm.document.valid_date', '10.05.2026')
        // Past expiry: the card says so next to the field.
        ->assertSee(__('personnel::common.labels.id_card_expired'));
});

it('keeps the expiry date optional', function (): void {
    [$component, $personnel] = idCardEditor();

    $component->call('store')->assertHasNoErrors();

    expect(PersonnelIdentityDocument::query()->where('tabel_no', $personnel->tabel_no)->sole()->valid_date)->toBeNull();
});
