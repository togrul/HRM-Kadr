<?php

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\AddPersonnel;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * 08.10.2026 auditi: işçi formasında server tərəfli yoxlama, normallaşdırma və
 * müqavilənin bitmə tarixinin işdən çıxma tarixindən ayrılması.
 */
function personnelFormUser(array $extraPermissions = []): User
{
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');
    $user = User::factory()->create();

    foreach (['add-personnels', 'edit-personnels', ...$extraPermissions] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);
    Livewire::actingAs($user);

    return $user;
}

function validPersonnelStep(string $tabelNo = 'VAL-1001', string $pin = 'VAL1001'): Testable
{
    return Livewire::test(AddPersonnel::class)
        ->set('step', 1)
        ->set('personalForm.personnel.tabel_no', $tabelNo)
        ->set('personalForm.personnel.name', 'Rəşad')
        ->set('personalForm.personnel.surname', 'Əliyev')
        ->set('personalForm.personnel.patronymic', 'Hikmət')
        ->set('personalForm.personnel.birthdate', '1990-01-01')
        ->set('personalForm.personnel.gender', 1)
        ->set('personalForm.personnel.nationality_id', 1)
        ->set('personalForm.personnel.mobile', '0501234567')
        ->set('personalForm.personnel.pin', $pin)
        ->set('personalForm.personnel.residental_address', 'Bakı')
        ->set('personalForm.personnel.registered_address', 'Bakı')
        ->set('personalForm.personnel.education_degree_id', 1)
        ->set('personalForm.personnel.structure_id', 1)
        ->set('personalForm.personnel.position_id', 1)
        ->set('personalForm.personnel.work_norm_id', 1)
        ->set('personalForm.personnel.join_work_date', '2020-01-01');
}

it('rejects the values the audit managed to save', function (string $field, mixed $value): void {
    personnelFormUser();

    validPersonnelStep()
        ->set('personalForm.personnel.'.$field, $value)
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.'.$field]);

    expect(Personnel::query()->where('tabel_no', 'VAL-1001')->exists())->toBeFalse();
})->with([
    'birthdate in the future' => ['birthdate', '2030-01-01'],
    'birthdate over a century ago' => ['birthdate', '1900-01-01'],
    'symbols as FİN' => ['pin', '!@#$%^&'],
    'six-character FİN' => ['pin', 'AB1234'],
    'letters as mobile' => ['mobile', 'abcdefghij'],
    'too short mobile' => ['mobile', '12345'],
    'letters as phone' => ['phone', 'abc'],
    'malformed email' => ['email', 'notanemail'],
    'negative tabel number' => ['tabel_no', '-5'],
    'zero tabel number' => ['tabel_no', '0'],
]);

it('rejects a contract signed after the start date and one ending before it', function (): void {
    personnelFormUser();

    validPersonnelStep()
        ->set('personalForm.personnel.contract_date', '2020-02-01')
        ->set('personalForm.personnel.contract_type', 'fixed')
        ->set('personalForm.personnel.contract_end_date', '2019-12-31')
        ->call('store')
        ->assertHasErrors([
            'personalForm.personnel.contract_date',
            'personalForm.personnel.contract_end_date',
        ]);
});

it('accepts a contract signed on or before the start date', function (string $contractDate): void {
    personnelFormUser();

    validPersonnelStep()
        ->set('personalForm.personnel.contract_date', $contractDate)
        ->call('store')
        ->assertHasNoErrors();
})->with(['2020-01-01', '2019-12-15']);

it('requires an end date only for a fixed-term contract', function (): void {
    personnelFormUser();

    validPersonnelStep('VAL-1002', 'VAL1002')
        ->set('personalForm.personnel.contract_type', 'fixed')
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.contract_end_date' => 'required']);

    validPersonnelStep('VAL-1003', 'VAL1003')
        ->set('personalForm.personnel.contract_type', 'indefinite')
        ->call('store')
        ->assertHasNoErrors();
});

it('enforces the minimum hiring age of fifteen on the start date', function (): void {
    personnelFormUser();

    validPersonnelStep('VAL-1004', 'VAL1004')
        ->set('personalForm.personnel.birthdate', '2005-01-02')
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.birthdate']);

    validPersonnelStep('VAL-1005', 'VAL1005')
        ->set('personalForm.personnel.birthdate', '2005-01-01')
        ->call('store')
        ->assertHasNoErrors();
});

it('caps probation at three months', function (string $unit, int $amount, bool $valid): void {
    personnelFormUser();

    $component = validPersonnelStep()
        ->set('personalForm.personnel.probation_unit', $unit)
        ->set('personalForm.personnel.probation_amount', $amount)
        ->call('store');

    $valid
        ? $component->assertHasNoErrors()
        : $component->assertHasErrors(['personalForm.personnel.probation_amount']);
})->with([
    ['month', 3, true],
    ['month', 4, false],
    ['week', 14, false],
    ['day', 92, true],
    ['day', 120, false],
]);

it('normalizes FİN, phone, email and patronymic before saving', function (): void {
    personnelFormUser();

    validPersonnelStep()
        ->set('personalForm.personnel.pin', ' ab1 2c3d ')
        ->set('personalForm.personnel.mobile', '+994 (50) 123-45-67')
        ->set('personalForm.personnel.phone', '012 444 55 66')
        ->set('personalForm.personnel.email', '  rashad@example.test ')
        ->set('personalForm.personnel.patronymic', 'Hikmət oğlu')
        ->call('store')
        ->assertHasNoErrors();

    $personnel = Personnel::query()->where('tabel_no', 'VAL-1001')->sole();

    expect($personnel->pin)->toBe('AB12C3D')
        ->and($personnel->mobile)->toBe('+994501234567')
        ->and($personnel->phone)->toBe('0124445566')
        ->and($personnel->email)->toBe('rashad@example.test')
        ->and($personnel->patronymic)->toBe('Hikmət')
        ->and($personnel->fullname_max)->toBe('Əliyev Rəşad Hikmət oğlu');
});

it('keeps FİN unique among working employees but allows re-hire and internal part-time', function (): void {
    personnelFormUser();

    // Fiksturdakı işləyən əməkdaşın FİN-i ABC1234-dür.
    validPersonnelStep()
        ->set('personalForm.personnel.pin', 'ABC1234')
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.pin' => 'unique']);

    validPersonnelStep('VAL-1006')
        ->set('personalForm.personnel.pin', 'ABC1234')
        ->set('personalForm.personnel.workplace_type', 'secondary')
        ->call('store')
        ->assertHasNoErrors();

    Personnel::query()->where('tabel_no', 'CRUD-BENCH-001')->sole()
        ->forceFill(['leave_work_date' => '2021-01-01'])->save();

    validPersonnelStep('VAL-1007')
        ->set('personalForm.personnel.pin', 'ABC1234')
        ->call('store')
        ->assertHasNoErrors();
});

it('keeps tabel numbers unique, deleted employees included, but ignores the record itself on edit', function (): void {
    personnelFormUser();

    validPersonnelStep('CRUD-BENCH-001')
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.tabel_no' => 'unique']);

    $personnel = Personnel::query()->where('tabel_no', 'CRUD-BENCH-001')->sole();

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->set('personalForm.personnel.pin', 'ABC1234')
        ->call('store')
        ->assertHasNoErrors(['personalForm.personnel.tabel_no', 'personalForm.personnel.pin']);

    $personnel->delete();

    validPersonnelStep('CRUD-BENCH-001', 'VAL1008')
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.tabel_no' => 'unique']);
});

it('clears a stale error once the field is corrected', function (): void {
    personnelFormUser();

    validPersonnelStep()
        ->set('personalForm.personnel.email', 'notanemail')
        ->set('personalForm.personnel.contract_type', 'fixed')
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.email', 'personalForm.personnel.contract_end_date'])
        ->set('personalForm.personnel.email', 'ok@example.test')
        ->assertHasNoErrors(['personalForm.personnel.email'])
        ->assertHasErrors(['personalForm.personnel.contract_end_date'])
        ->set('personalForm.personnel.contract_type', 'indefinite')
        ->assertHasNoErrors(['personalForm.personnel.contract_end_date']);
});

it('stores the contract end date separately and never dismisses through the form', function (): void {
    personnelFormUser();

    validPersonnelStep()
        ->set('personalForm.personnel.contract_type', 'fixed')
        ->set('personalForm.personnel.contract_end_date', '2020-06-30')
        // Livewire vəziyyətinə əl ilə yazılsa belə işdən çıxma tarixi saxlanmamalıdır.
        ->set('personalForm.personnel.leave_work_date', '2020-02-01')
        ->call('store')
        ->assertHasNoErrors();

    $personnel = Personnel::query()->where('tabel_no', 'VAL-1001')->sole();

    expect($personnel->contract_end_date->toDateString())->toBe('2020-06-30')
        ->and($personnel->leave_work_date)->toBeNull();
});

it('leaves a dismissal recorded by a termination order untouched when the card is edited', function (): void {
    personnelFormUser();

    $personnel = Personnel::query()->where('tabel_no', 'CRUD-BENCH-001')->sole();
    $personnel->forceFill(['leave_work_date' => '2024-05-31'])->save();

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->assertDontSee('personalForm.personnel.leave_work_date')
        ->set('personalForm.personnel.leave_work_date', null)
        ->set('personalForm.personnel.contract_end_date', '2025-12-31')
        ->call('store')
        ->assertHasNoErrors();

    $personnel->refresh();

    expect((string) $personnel->getRawOriginal('leave_work_date'))->toStartWith('2024-05-31')
        ->and($personnel->contract_end_date->toDateString())->toBe('2025-12-31');
});

it('words the shared length messages as requirements', function (): void {
    app()->setLocale('az');

    expect(__('validation.min.string', ['attribute' => 'FİN', 'min' => 7]))
        ->toContain('ən azı 7 simvoldan ibarət olmalıdır')
        ->not->toContain('ola bilər');
});
