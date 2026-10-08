<?php

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Variables\OrderEmployeeVariableResolver;
use App\Modules\Personnel\Application\Services\PersonnelProfileReadService;
use App\Modules\Personnel\Livewire\AddPersonnel;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Livewire\DeletePersonnel;
use App\Modules\Personnel\Livewire\TablePanel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use App\Modules\Personnel\Services\PersonnelRowActionService;
use App\Services\StructureService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * 08.10.2026 auditi: kart, siyahı və sətir əməliyyatları.
 */
function personnelListUser(array $permissions = ['show-personnels', 'add-personnels', 'edit-personnels', 'delete-personnels']): User
{
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);
    Livewire::actingAs($user);
    test()->actingAs($user);

    // Siyahı istifadəçinin rollarına bağlı strukturları göstərir; testdə hamısı 1-dir.
    test()->mock(StructureService::class, fn ($mock) => $mock->shouldReceive('getAccessibleStructures')->andReturn([1]));

    return $user;
}

function fixturePersonnel(): Personnel
{
    return Personnel::query()->withTrashed()->where('tabel_no', 'CRUD-BENCH-001')->sole();
}

function secondPersonnel(string $tabelNo = 'LIST-2', string $surname = 'Qasımov'): Personnel
{
    return Personnel::withoutEvents(fn (): Personnel => Personnel::query()->create([
        'tabel_no' => $tabelNo,
        'surname' => $surname,
        'name' => 'Elçin',
        'patronymic' => 'Vüqar',
        'birthdate' => '1988-05-05',
        'gender' => 1,
        'mobile' => '0501112233',
        'nationality_id' => 1,
        'pin' => 'LST'.substr(md5($tabelNo), 0, 4),
        'residental_address' => 'Bakı',
        'education_degree_id' => 1,
        'structure_id' => 1,
        'position_id' => 1,
        'work_norm_id' => 1,
        'join_work_date' => '2021-01-01',
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

// --- 3. Ata adı şəkilçisi ---------------------------------------------------------

it('does not double the suffix of a legacy patronymic anywhere it is rendered', function (): void {
    personnelListUser();

    $personnel = fixturePersonnel();
    // Köhnə qeyd: şəkilçi bazada saxlanılıb (mutator-dan yan keçirik).
    DB::table('personnels')->where('id', $personnel->id)->update(['patronymic' => 'Hikmət oğlu']);
    $personnel->refresh();

    expect($personnel->fullname_max)->toBe('Benchmark Personnel Hikmət oğlu');

    $variables = app(OrderEmployeeVariableResolver::class)->resolve($personnel);

    expect($variables['employee.full_name_with_suffix'])->toBe('Benchmark Personnel Hikmət oğlu')
        ->and($variables['employee.patronymic'])->toBe('Hikmət')
        ->and($variables['employee.full_name_dative'])->toBe('Benchmark Personnel Hikmət oğluna');
});

it('stores a patronymic without its suffix whatever path writes it', function (): void {
    personnelListUser();

    $personnel = fixturePersonnel();
    $personnel->update(['patronymic' => 'Hikmət QIZI', 'gender' => 2]);

    expect($personnel->refresh()->patronymic)->toBe('Hikmət')
        ->and($personnel->fullname_max)->toBe('Benchmark Personnel Hikmət qızı');
});

// --- 4. Kart: "Əməyin ödənilməsi" və əmək fəaliyyəti ------------------------------

it('labels the pay system and the work schedule separately on the card', function (): void {
    personnelListUser();

    $personnel = fixturePersonnel();
    $personnel->forceFill(['work_schedule' => 'five_day'])->save();

    $reader = app(PersonnelProfileReadService::class);
    $rows = collect($reader->personalRows($reader->load($personnel->refresh())))->keyBy('label');

    expect($rows[__('personnel::common.labels.work_norms')]['value'])->toBe('Tam ştat')
        ->and($rows[__('personnel::common.labels.work_schedule')]['value'])->toBe(__('personnel::common.employment.work_schedule.five_day'));
});

it('shows the current post on the card even without a labour activity row', function (): void {
    personnelListUser();

    $personnel = fixturePersonnel();
    $reader = app(PersonnelProfileReadService::class);
    $timeline = $reader->careerTimeline($reader->load($personnel));

    expect($timeline)->toHaveCount(1)
        ->and($timeline[0]['title'])->toBe('Benchmark Position')
        ->and($timeline[0]['is_current'])->toBeTrue()
        ->and($timeline[0]['from'])->toBe('2020');

    $personnel->forceFill(['leave_work_date' => '2024-01-01'])->save();

    expect($reader->careerTimeline($reader->load($personnel->refresh())))->toBe([]);
});

it('records the current labour activity when an approver adds an employee directly', function (): void {
    personnelListUser(['show-personnels', 'add-personnels', 'edit-personnels', 'confirmation-general']);

    Livewire::test(AddPersonnel::class)
        ->set('step', 1)
        ->set('personalForm.personnel.tabel_no', 'LAB-1')
        ->set('personalForm.personnel.name', 'Rəşad')
        ->set('personalForm.personnel.surname', 'Əliyev')
        ->set('personalForm.personnel.patronymic', 'Hikmət')
        ->set('personalForm.personnel.birthdate', '1990-01-01')
        ->set('personalForm.personnel.gender', 1)
        ->set('personalForm.personnel.nationality_id', 1)
        ->set('personalForm.personnel.mobile', '0501234567')
        ->set('personalForm.personnel.pin', 'LAB0001')
        ->set('personalForm.personnel.residental_address', 'Bakı')
        ->set('personalForm.personnel.registered_address', 'Bakı')
        ->set('personalForm.personnel.education_degree_id', 1)
        ->set('personalForm.personnel.structure_id', 1)
        ->set('personalForm.personnel.position_id', 1)
        ->set('personalForm.personnel.work_norm_id', 1)
        ->set('personalForm.personnel.join_work_date', '2024-03-01')
        ->call('store')
        ->assertHasNoErrors();

    $activity = Personnel::query()->where('tabel_no', 'LAB-1')->sole()->laborActivities()->sole();

    expect((bool) $activity->is_current)->toBeTrue()
        ->and($activity->position)->toBe('Benchmark Position')
        ->and($activity->getRawOriginal('join_date'))->toStartWith('2024-03-01');
});

it('flags an expired fixed-term contract without dismissing the employee', function (): void {
    personnelListUser();

    $personnel = fixturePersonnel();
    $personnel->forceFill(['contract_type' => 'fixed', 'contract_end_date' => now()->subDay()->toDateString()])->save();
    $personnel->refresh();

    $reader = app(PersonnelProfileReadService::class);

    expect($reader->contractExpired($personnel))->toBeTrue()
        ->and($personnel->leave_work_date)->toBeNull()
        ->and($reader->statusLabel($personnel))->toBe(__('personnel::common.states.at_work'));
});

// --- 5. Silmə əsas açarla -----------------------------------------------------------

it('addresses delete, restore and force-delete row actions by primary key', function (): void {
    $personnel = (new Personnel)->forceFill(['id' => 42, 'tabel_no' => '-5']);
    $service = app(PersonnelRowActionService::class);
    $capabilities = ['can_edit' => true, 'can_delete' => true];

    $delete = collect($service->build($personnel, 'current', $capabilities))->firstWhere('id', 'delete');
    $deleted = collect($service->build($personnel, 'deleted', $capabilities))->keyBy('id');

    expect($delete->actionPayload['value'])->toBe(42)
        ->and($deleted['restore']->actionPayload['value'])->toBe(42)
        ->and($deleted['force-delete']->actionPayload['value'])->toBe(42);
});

it('deletes, restores and force-deletes the right employee by id', function (): void {
    personnelListUser();

    $personnel = fixturePersonnel();
    // Başqa əməkdaşın tabel nömrəsi birincinin id-si ilə eynidir — id/tabel qarışıqlığı
    // olsaydı səhv qeyd silinərdi.
    $decoy = secondPersonnel((string) $personnel->id);

    Livewire::test(DeletePersonnel::class)
        ->call('setDeletePersonnel', $personnel->id)
        ->assertSet('personnelId', $personnel->id)
        ->call('deletePersonnel');

    expect(fixturePersonnel()->trashed())->toBeTrue()
        ->and($decoy->refresh()->trashed())->toBeFalse();

    $list = Livewire::test(AllPersonnel::class);

    // Aktiv əməkdaş "tam silmə" ilə birbaşa yox edilə bilməz.
    $list->call('handleRowAction', 'force-delete', ['type' => 'force-delete', 'value' => (string) $decoy->id]);
    expect(Personnel::query()->whereKey($decoy->id)->exists())->toBeTrue();

    $list->call('handleRowAction', 'restore', ['type' => 'restore', 'value' => (string) $personnel->id]);
    expect(fixturePersonnel()->trashed())->toBeFalse();

    $decoy->delete();
    $list->call('handleRowAction', 'force-delete', ['type' => 'force-delete', 'value' => (string) $decoy->id]);
    expect(Personnel::withTrashed()->whereKey($decoy->id)->exists())->toBeFalse()
        ->and(Personnel::query()->whereKey($personnel->id)->exists())->toBeTrue();
});

it('ignores a tabel number passed where an id is expected', function (): void {
    personnelListUser();

    Livewire::test(DeletePersonnel::class)
        ->call('setDeletePersonnel', '-5')
        ->assertSet('personnelId', null)
        ->call('setDeletePersonnel', 'CRUD-BENCH-001')
        ->assertSet('personnelId', null);
});

it('enforces tabel number uniqueness in the database too', function (): void {
    personnelListUser();

    expect(fn () => secondPersonnel('CRUD-BENCH-001'))->toThrow(QueryException::class);
});

// --- 6. Silinmiş əməkdaşın statusu ---------------------------------------------------

it('shows deleted employees as deleted in the deleted list', function (): void {
    personnelListUser(['show-personnels', 'edit-personnels', 'delete-personnels', 'access-admin']);

    fixturePersonnel()->delete();

    Livewire::test(TablePanel::class, ['status' => 'deleted'])
        ->assertSee(__('personnel::common.states.deleted'))
        ->assertDontSee(__('personnel::common.states.at_work'));
});

// --- 7. Vətəndaşlıq və vəzifə seçimləri ---------------------------------------------

it('puts Azerbaijan first and preselects it for a new employee', function (): void {
    personnelListUser();

    $azerbaijanId = (int) DB::table('countries')->where('code', 'AZ')->value('id');
    DB::table('countries')->insert(['id' => 2, 'code' => 'AL']);
    DB::table('country_translations')->insert([
        ['country_id' => 2, 'locale' => 'az', 'title' => 'Albaniya'],
        ['country_id' => $azerbaijanId, 'locale' => 'az', 'title' => 'Azərbaycan'],
    ]);
    app()->setLocale('az');

    $component = Livewire::test(AddPersonnel::class)
        ->assertSet('personalForm.personnel.nationality_id', $azerbaijanId);

    expect((int) $component->instance()->nationalityOptions[0]['id'])->toBe($azerbaijanId);
});

it('offers the positions of the chosen structure staff schedule, or all of them with a hint', function (): void {
    personnelListUser();

    DB::table('positions')->insert([['id' => 2, 'name' => 'Mühasib'], ['id' => 3, 'name' => 'Sürücü']]);
    DB::table('structures')->insert(['id' => 2, 'name' => 'Anbar', 'shortname' => 'ANB']);
    DB::table('staff_schedules')->insert(['structure_id' => 1, 'position_id' => 2, 'total' => 1, 'filled' => 0, 'vacant' => 1]);

    $component = Livewire::test(AddPersonnel::class)
        ->set('personalForm.personnel.position_id', 3)
        ->set('personalForm.personnel.structure_id', 1)
        // Sürücü 1 saylı strukturun ştatında yoxdur — seçim sıfırlanır.
        ->assertSet('personalForm.personnel.position_id', null);

    expect(array_column($component->instance()->positionOptions, 'id'))->toBe([2])
        ->and($component->instance()->positionListFallsBackToAll)->toBeFalse();

    $component->set('personalForm.personnel.structure_id', 2)
        ->assertSee(__('personnel::common.hints.positions_without_staff_schedule'));

    expect(array_column($component->instance()->positionOptions, 'id'))->toEqualCanonicalizing([1, 2, 3])
        ->and($component->instance()->positionListFallsBackToAll)->toBeTrue();
});

// --- 8. Status sayğacları axtarışa uyğun --------------------------------------------

it('keeps the status filter counts in step with the search', function (): void {
    personnelListUser();
    secondPersonnel('LIST-2', 'Qasımov');

    $active = __('personnel::common.labels.active');

    Livewire::test(AllPersonnel::class)
        ->assertSeeHtml('data-option-label="'.$active.' · 2"')
        ->set('search', 'Qasımov')
        ->assertSeeHtml('data-option-label="'.$active.' · 1"')
        ->assertDontSeeHtml('data-option-label="'.$active.' · 2"');
});
