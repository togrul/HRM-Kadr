<?php

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Livewire\OrderComposer;
use App\Modules\Personnel\Application\Services\PersonnelAssignmentGuard;
use App\Modules\Personnel\Contracts\GuardsPersonnelAssignment;
use App\Modules\Personnel\Livewire\AddPersonnel;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use App\Services\PersonnelRelationsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * Mövcud əməkdaşın struktur bölməsi və vəzifəsi yalnız əmrlə dəyişir: redaktə formu,
 * birbaşa Eloquent yazması və əmək fəaliyyəti siyahısı onu dəyişə bilmir; köçürmə əmri
 * (tətbiq + ləğv) və işə qəbul isə dəyişir.
 */

function assignmentGuardUser(array $permissions = ['add-personnels', 'edit-personnels']): User
{
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

/**
 * @return array{0: Personnel, 1: Structure, 2: Position}
 */
function assignmentGuardFixture(User $user): array
{
    $personnel = app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);
    $structure = Structure::query()->create(['name' => 'Hədəf bölmə', 'shortname' => 'HB']);
    $position = Position::query()->create(['name' => 'Hədəf vəzifə']);

    return [$personnel, $structure, $position];
}

function assignmentGuardMaster(string $relative, string $text): void
{
    $phpWord = new PhpWord;
    $phpWord->addSection()->addText($text);
    $tmp = tempnam(sys_get_temp_dir(), 'mst_').'.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
    Storage::disk('local')->put($relative, (string) file_get_contents($tmp));
    @unlink($tmp);
}

it('rejects a crafted Livewire edit of structure and position and leaves the row untouched', function (): void {
    $user = assignmentGuardUser();
    [$personnel, $structure, $position] = assignmentGuardFixture($user);
    Livewire::actingAs($user);

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->set('personalForm.personnel.name', 'Dəyişdirilmiş')
        ->set('personalForm.personnel.structure_id', $structure->id)
        ->set('personalForm.personnel.position_id', $position->id)
        ->call('store')
        ->assertHasErrors([
            'personalForm.personnel.structure_id',
            'personalForm.personnel.position_id',
        ])
        ->assertSee(__('personnel::common.validation.assignment_order_only'));

    $fresh = $personnel->fresh();
    expect((int) $fresh->structure_id)->toBe((int) $personnel->structure_id)
        ->and((int) $fresh->position_id)->toBe((int) $personnel->position_id)
        ->and($fresh->name)->toBe($personnel->name);
});

it('still saves an edit that keeps structure and position as they are', function (): void {
    $user = assignmentGuardUser();
    [$personnel] = assignmentGuardFixture($user);
    Livewire::actingAs($user);

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->set('personalForm.personnel.name', 'Yeniad')
        ->call('store')
        ->assertHasNoErrors();

    $fresh = $personnel->fresh();
    expect($fresh->name)->toBe('Yeniad')
        ->and((int) $fresh->structure_id)->toBe((int) $personnel->structure_id);
});

it('shows the order-only hint next to the locked fields', function (): void {
    $user = assignmentGuardUser();
    [$personnel] = assignmentGuardFixture($user);
    Livewire::actingAs($user);

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->assertSee(__('personnel::common.hints.assignment_order_only'));
});

it('blocks any other Eloquent write path at the model layer', function (): void {
    $user = assignmentGuardUser();
    [$personnel, $structure, $position] = assignmentGuardFixture($user);

    expect(fn () => $personnel->fresh()->update(['structure_id' => $structure->id]))
        ->toThrow(ValidationException::class);
    expect(fn () => $personnel->fresh()->forceFill(['position_id' => $position->id])->save())
        ->toThrow(ValidationException::class);

    $fresh = $personnel->fresh();
    expect((int) $fresh->structure_id)->toBe((int) $personnel->structure_id)
        ->and((int) $fresh->position_id)->toBe((int) $personnel->position_id);

    // Qorunmayan sahələr əvvəlki kimi yazılır.
    $personnel->fresh()->update(['phone' => '0121234567']);
    expect($personnel->fresh()->phone)->toBe('0121234567');
});

it('lets an explicitly authorised scope change the assignment and closes it afterwards', function (): void {
    $user = assignmentGuardUser();
    [$personnel, $structure] = assignmentGuardFixture($user);
    $guard = app(GuardsPersonnelAssignment::class);

    $guard->allow(fn (): bool => $personnel->fresh()->update(['structure_id' => $structure->id]));
    expect((int) $personnel->fresh()->structure_id)->toBe($structure->id)
        ->and($guard->isAllowed())->toBeFalse();

    // İstisna atılsa da kontekst bağlanır.
    try {
        $guard->allow(fn () => throw new RuntimeException('boom'));
    } catch (RuntimeException) {
    }
    expect($guard->isAllowed())->toBeFalse();
});

it('exposes the guarded attributes through a field-group registry', function (): void {
    $guard = app(PersonnelAssignmentGuard::class);

    expect($guard)->toBe(app(GuardsPersonnelAssignment::class))
        ->and($guard->fieldGroups())->toHaveKey(PersonnelAssignmentGuard::GROUP_ASSIGNMENT)
        ->and($guard->guardedAttributes())->toBe(['structure_id', 'position_id'])
        ->and($guard->isGuarded('structure_id'))->toBeTrue()
        ->and($guard->isGuarded('phone'))->toBeFalse();
});

it('does not move an existing employee through the labour-activity list', function (): void {
    $user = assignmentGuardUser();
    [$personnel, $structure, $position] = assignmentGuardFixture($user);

    app(PersonnelRelationsService::class)->update($personnel->fresh(), [
        'labor_activities' => [[
            'company_name' => 'Şirkət',
            'position' => 'Hədəf vəzifə',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'join_date' => '2021-01-01',
            'is_current' => true,
            'leave_date' => null,
        ]],
    ]);

    $fresh = $personnel->fresh();
    expect((int) $fresh->structure_id)->toBe((int) $personnel->structure_id)
        ->and((int) $fresh->position_id)->toBe((int) $personnel->position_id)
        ->and($fresh->laborActivities()->where('position_id', $position->id)->exists())->toBeTrue();
});

it('creates a new employee with the chosen structure and position', function (): void {
    $user = assignmentGuardUser();
    [, $structure, $position] = assignmentGuardFixture($user);
    Livewire::actingAs($user);

    Livewire::test(AddPersonnel::class)
        ->set('step', 1)
        ->set('personalForm.personnel.tabel_no', 'GUARD-1001')
        ->set('personalForm.personnel.name', 'Yeni')
        ->set('personalForm.personnel.surname', 'Əməkdaş')
        ->set('personalForm.personnel.patronymic', 'Test')
        ->set('personalForm.personnel.birthdate', '1990-01-01')
        ->set('personalForm.personnel.gender', 1)
        ->set('personalForm.personnel.nationality_id', 1)
        ->set('personalForm.personnel.mobile', '0500000000')
        ->set('personalForm.personnel.pin', 'GRD1234')
        ->set('personalForm.personnel.residental_address', 'Bakı')
        ->set('personalForm.personnel.registered_address', 'Bakı')
        ->set('personalForm.personnel.education_degree_id', 1)
        ->set('personalForm.personnel.structure_id', $structure->id)
        ->set('personalForm.personnel.position_id', $position->id)
        ->set('personalForm.personnel.work_norm_id', 1)
        ->set('personalForm.personnel.join_work_date', '2020-01-01')
        ->call('store')
        ->assertHasNoErrors();

    $created = Personnel::query()->where('tabel_no', 'GUARD-1001')->sole();
    expect((int) $created->structure_id)->toBe($structure->id)
        ->and((int) $created->position_id)->toBe($position->id);
});

it('moves the employee on an approved transfer order and moves them back when it is cancelled', function (): void {
    Storage::fake('local');
    $user = assignmentGuardUser(['add-orders']);
    [$personnel, $structure, $position] = assignmentGuardFixture($user);
    $originalStructure = (int) $personnel->structure_id;
    $originalPosition = (int) $personnel->position_id;

    assignmentGuardMaster('order-templates/move.docx', 'İşçi ${var_1} ${var_2} keçirilsin.');
    OrderWordTemplate::create([
        'code' => 'move',
        'label' => 'Başqa işə keçirilmə',
        'effect' => 'transfer',
        'docx_path' => 'order-templates/move.docx',
        'variables' => [
            ['token' => 'var_1', 'label' => 'Yeni iş yeri', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_1', 'type' => 'structure'], 'effect_role' => 'new_structure'],
            ['token' => 'var_2', 'label' => 'Yeni vəzifə', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'position'], 'effect_role' => 'new_position'],
        ],
        'is_active' => true,
    ]);
    StaffSchedule::query()->create([
        'structure_id' => $structure->id, 'position_id' => $position->id, 'total' => 1, 'filled' => 0, 'vacant' => 1,
    ]);

    test()->actingAs($user);
    Livewire::test(OrderComposer::class, ['presetCode' => 'move', 'personnelId' => $personnel->id])
        ->set('orderNumber', '901-K')
        ->set('fields', ['var_1' => (string) $structure->id, 'var_2' => (string) $position->id])
        ->call('issue');

    $order = OrderLog::query()->where('order_no', '901-K')->firstOrFail();
    $transitions = app(OrderStatusTransitionService::class);

    $transitions->approve($order);
    $moved = $personnel->fresh();
    expect((int) $moved->structure_id)->toBe($structure->id)
        ->and((int) $moved->position_id)->toBe($position->id);

    $transitions->cancel($order->fresh(), 'Test üçün geri alınır');
    $restored = $personnel->fresh();
    expect((int) $order->fresh()->status_id)->toBe(OrderStatusEnum::CANCELLED->value)
        ->and((int) $restored->structure_id)->toBe($originalStructure)
        ->and((int) $restored->position_id)->toBe($originalPosition)
        ->and(app(GuardsPersonnelAssignment::class)->isAllowed())->toBeFalse();
});
