<?php

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\PersonnelChangePolicy;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use App\Modules\Orders\Livewire\OrderComposer;
use App\Modules\Personnel\Application\Services\PersonnelAssignmentGuard;
use App\Modules\Personnel\Application\Services\PersonnelChangeGuard;
use App\Modules\Personnel\Application\Services\PersonnelFieldGroupRegistry;
use App\Modules\Personnel\Contracts\GuardsPersonnelAssignment;
use App\Modules\Personnel\Contracts\GuardsPersonnelChanges;
use App\Modules\Personnel\Contracts\ManagesPersonnelChangePolicy;
use App\Modules\Personnel\Contracts\PersonnelChangeMode;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * Dəyişiklik siyasəti: hər sahə qrupu `free` / `journal` / `order` rejimindədir. Model
 * səviyyəsi, işçi forması (hazırlanmış Livewire sorğuları daxil) və əmr effektləri siyasətə
 * uyğun davranır.
 */

function changePolicyUser(array $permissions = ['add-personnels', 'edit-personnels']): User
{
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');
    $user = grantAllStructures(User::factory()->create());

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

function changePolicyPersonnel(User $user): Personnel
{
    return app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user)->fresh();
}

function changePolicySet(string $group, PersonnelChangeMode $mode): void
{
    app(ManagesPersonnelChangePolicy::class)->setMode($group, $mode->value);
}

function changePolicyMaster(string $relative, string $text): void
{
    $phpWord = new PhpWord;
    $phpWord->addSection()->addText($text);
    $tmp = tempnam(sys_get_temp_dir(), 'mst_').'.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
    Storage::disk('local')->put($relative, (string) file_get_contents($tmp));
    @unlink($tmp);
}

function changePolicyJournalEntries(): \Illuminate\Support\Collection
{
    return Activity::query()->where('event', 'change_policy_journal')->get();
}

it('uses the default modes while no policy row exists', function (): void {
    $policy = app(ManagesPersonnelChangePolicy::class);

    expect($policy->modeFor('assignment'))->toBe(PersonnelChangeMode::Order)
        ->and($policy->modeFor('salary'))->toBe(PersonnelChangeMode::Order)
        ->and($policy->modeFor('surname'))->toBe(PersonnelChangeMode::Order)
        ->and($policy->modeFor('employment_dates'))->toBe(PersonnelChangeMode::Journal)
        ->and($policy->modeFor('contact'))->toBe(PersonnelChangeMode::Free)
        ->and($policy->modeFor('family'))->toBe(PersonnelChangeMode::Free)
        ->and($policy->modeFor('documents'))->toBe(PersonnelChangeMode::Free)
        ->and($policy->modeFor('photo_notes'))->toBe(PersonnelChangeMode::Free)
        ->and(collect($policy->rows())->pluck('customized')->unique()->all())->toBe([false]);
});

it('resolves every guard name to one shared instance', function (): void {
    $guard = app(PersonnelChangeGuard::class);

    expect(app(GuardsPersonnelChanges::class))->toBe($guard)
        ->and(app(GuardsPersonnelAssignment::class))->toBe($guard)
        ->and(app(PersonnelAssignmentGuard::class))->toBe($guard)
        ->and($guard->restrictedAttributes())->toContain('structure_id', 'surname', 'join_work_date', 'leave_work_date')
        ->and($guard->restrictedAttributes())->not->toContain('phone');
});

it('lets free groups change through any write path', function (): void {
    $user = changePolicyUser();
    $personnel = changePolicyPersonnel($user);
    changePolicySet('surname', PersonnelChangeMode::Free);

    $personnel->update(['surname' => 'Sərbəstov', 'phone' => '0121112233']);

    expect($personnel->fresh()->surname)->toBe('Sərbəstov')
        ->and(changePolicyJournalEntries())->toHaveCount(0);
});

it('rejects an order-only field outside an order at the model layer', function (): void {
    $user = changePolicyUser();
    $personnel = changePolicyPersonnel($user);

    expect(fn () => $personnel->fresh()->update(['surname' => 'Əmrsiz']))->toThrow(ValidationException::class)
        ->and(fn () => $personnel->fresh()->update(['join_work_date' => '2019-05-05']))->toThrow(ValidationException::class)
        ->and(fn () => $personnel->fresh()->forceFill(['leave_work_date' => '2025-01-31'])->save())->toThrow(ValidationException::class);

    $fresh = $personnel->fresh();
    expect($fresh->surname)->toBe($personnel->surname)
        ->and($fresh->join_work_date->toDateString())->toBe($personnel->join_work_date->toDateString())
        ->and($fresh->leave_work_date)->toBeNull();
});

it('lets an order effect write only the groups the registry gives it', function (): void {
    $user = changePolicyUser();
    $personnel = changePolicyPersonnel($user);
    $guard = app(GuardsPersonnelChanges::class);

    $guard->allowForEffect('termination', fn () => $personnel->fresh()->forceFill(['leave_work_date' => '2025-01-31'])->save());
    expect($personnel->fresh()->leave_work_date->toDateString())->toBe('2025-01-31');

    // Köçürmə effekti soyadı yaza bilməz.
    expect(fn () => $guard->allowForEffect('transfer', fn () => $personnel->fresh()->update(['surname' => 'Köçürülən'])))
        ->toThrow(ValidationException::class)
        ->and($guard->isAllowed())->toBeFalse()
        ->and(app(PersonnelFieldGroupRegistry::class)->groupsForEffect('hire'))->toBe(['assignment', 'salary', 'employment_dates']);
});

it('requires a reason in journal mode and writes old and new values to the activity log', function (): void {
    $user = changePolicyUser();
    $this->actingAs($user);
    $personnel = changePolicyPersonnel($user);
    changePolicySet('surname', PersonnelChangeMode::Journal);
    $guard = app(GuardsPersonnelChanges::class);

    expect(fn () => $personnel->fresh()->update(['surname' => 'Səbəbsiz']))->toThrow(ValidationException::class);
    // 5 simvoldan qısa səbəb kifayət deyil.
    expect(fn () => $guard->withReason('abc', fn () => $personnel->fresh()->update(['surname' => 'Qısa'])))->toThrow(ValidationException::class);

    $guard->withReason('Pasportda yazı səhvi', fn () => $personnel->fresh()->update(['surname' => 'Jurnalov']));

    $entry = changePolicyJournalEntries()->sole();
    expect($personnel->fresh()->surname)->toBe('Jurnalov')
        ->and($entry->properties['field_group'])->toBe('surname')
        ->and($entry->properties['reason'])->toBe('Pasportda yazı səhvi')
        ->and($entry->properties['old'])->toBe(['surname' => $personnel->surname])
        ->and($entry->properties['attributes'])->toBe(['surname' => 'Jurnalov'])
        ->and((int) $entry->causer_id)->toBe($user->id)
        ->and((int) $entry->subject_id)->toBe($personnel->id);
});

it('rejects crafted Livewire edits of order-only fields with a field error', function (): void {
    $user = changePolicyUser();
    $personnel = changePolicyPersonnel($user);
    Livewire::actingAs($user);

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->assertSee(__('personnel::change_policy.badges.order'))
        ->set('personalForm.personnel.surname', 'Saxtayev')
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.surname']);

    expect($personnel->fresh()->surname)->toBe($personnel->surname);
});

it('lets a mistyped hire date be corrected only with a stated reason by default', function (): void {
    $user = changePolicyUser();
    $this->actingAs($user);
    $personnel = changePolicyPersonnel($user);
    $guard = app(GuardsPersonnelChanges::class);

    expect(fn () => $personnel->fresh()->update(['join_work_date' => '2019-02-01']))->toThrow(ValidationException::class)
        ->and($personnel->fresh()->join_work_date->toDateString())->toBe($personnel->join_work_date->toDateString());

    $guard->withReason('Əmrdə tarix səhv köçürülüb', fn () => $personnel->fresh()->update(['join_work_date' => '2019-02-01']));

    expect($personnel->fresh()->join_work_date->toDateString())->toBe('2019-02-01')
        ->and(changePolicyJournalEntries()->sole()->properties['field_group'])->toBe('employment_dates');
});

it('unlocks the wizard fields of a group switched to free', function (): void {
    $user = changePolicyUser();
    $personnel = changePolicyPersonnel($user);
    changePolicySet('surname', PersonnelChangeMode::Free);
    changePolicySet('assignment', PersonnelChangeMode::Free);
    Livewire::actingAs($user);

    $component = Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()]);
    expect($component->instance()->fieldPolicies)->not->toHaveKeys(['surname', 'structure_id'])
        ->toHaveKey('join_work_date');

    $component->set('personalForm.personnel.surname', 'Yenisoy')
        ->call('store')
        ->assertHasNoErrors();

    expect($personnel->fresh()->surname)->toBe('Yenisoy');
});

it('asks for a reason in the wizard only when a journal field changed', function (): void {
    $user = changePolicyUser();
    $this->actingAs($user);
    $personnel = changePolicyPersonnel($user);
    changePolicySet('contact', PersonnelChangeMode::Journal);
    Livewire::actingAs($user);

    $component = Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->assertDontSee(__('personnel::change_policy.reason.label'))
        ->set('personalForm.personnel.name', 'Adıdəyişən')
        ->call('store')
        ->assertHasNoErrors();
    expect(changePolicyJournalEntries())->toHaveCount(0);

    $component->set('personalForm.personnel.phone', '0125556677')
        ->assertSee(__('personnel::change_policy.reason.label'))
        ->call('store')
        ->assertHasErrors(['changeReason']);
    expect($personnel->fresh()->phone)->not->toBe('0125556677');

    $component->set('changeReason', 'Əməkdaş nömrəsini dəyişib')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('changeReason', '');

    $entry = changePolicyJournalEntries()->sole();
    expect($personnel->fresh()->phone)->toBe('0125556677')
        ->and($entry->properties['field_group'])->toBe('contact')
        ->and($entry->properties['reason'])->toBe('Əməkdaş nömrəsini dəyişib')
        ->and($entry->properties['attributes'])->toBe(['phone' => '0125556677']);
});

it('guards the family list by its group mode', function (): void {
    $user = changePolicyUser();
    $this->actingAs($user);
    $personnel = changePolicyPersonnel($user);
    $personnel->kinships()->create(['kinship_id' => 1, 'fullname' => 'Əliyeva Sara', 'birthdate' => '1962-03-04', 'birth_place' => 'Bakı', 'company_name' => 'Pensiyaçı', 'position' => '-', 'registered_address' => 'Bakı', 'residental_address' => 'Bakı']);
    changePolicySet('family', PersonnelChangeMode::Order);
    Livewire::actingAs($user);

    $open = fn () => Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])->call('selectStep', 7);

    // «Ailə» addımı dəyişmədən saxlananda (uşaq komponentin qaytardığı vəziyyət) rədd olunmur.
    $component = $open();
    $component->call('handleChildSaveApproved', 7, $component->instance()->activeStepChildState())
        ->assertHasNoErrors();

    $added = ['kinship_id' => 2, 'fullname' => 'Əliyev Vəli', 'birthdate' => '01.01.1960', 'birth_place' => 'Bakı', 'company_name' => 'Pensiyaçı', 'position' => '-', 'registered_address' => 'Bakı', 'residental_address' => 'Bakı', 'row_key' => 'crafted-row'];
    $crafted = function ($component) use ($added): array {
        $state = $component->instance()->activeStepChildState();
        $state['kinshipList'][] = $added;

        return $state;
    };

    $component = $open();
    $component->call('handleChildSaveApproved', 7, $crafted($component))
        ->assertHasErrors(['changePolicy.family']);
    expect($personnel->kinships()->count())->toBe(1);

    changePolicySet('family', PersonnelChangeMode::Journal);

    $component = $open();
    $payload = $crafted($component);
    $component->call('handleChildSaveApproved', 7, $payload)
        ->assertHasErrors(['changeReason'])
        ->set('changeReason', 'Ailə tərkibi yeniləndi')
        ->call('handleChildSaveApproved', 7, $payload)
        ->assertHasNoErrors();

    $entry = changePolicyJournalEntries()->sole();
    expect($personnel->kinships()->count())->toBe(2)
        ->and($entry->properties['field_group'])->toBe('family')
        ->and($entry->properties['old'])->toBe(['kinships' => 1])
        ->and($entry->properties['attributes'])->toBe(['kinships' => 2]);
});

it('does not mistake an untouched documents step for a change', function (): void {
    $user = changePolicyUser();
    $personnel = changePolicyPersonnel($user);
    changePolicySet('documents', PersonnelChangeMode::Order);
    Livewire::actingAs($user);

    $component = Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])->call('selectStep', 2);
    $state = $component->instance()->activeStepChildState();
    $component->call('handleChildSaveApproved', 2, $state)
        ->assertHasNoErrors();

    $state['passportsList'][] = ['series' => 'C', 'number' => '01234567', 'given_date' => '01.01.2020', 'valid_date' => '01.01.2030', 'row_key' => 'crafted-passport'];
    $component->call('handleChildSaveApproved', 2, $state)
        ->assertHasErrors(['changePolicy.documents']);
});

it('applies a surname change order while the surname is order-only and rolls it back on cancel', function (): void {
    Storage::fake('local');
    $user = changePolicyUser(['add-orders']);
    $personnel = changePolicyPersonnel($user);
    $original = $personnel->surname;

    changePolicyMaster('order-templates/surname.docx', 'Soyad ${var_1} olsun.');
    OrderWordTemplate::create([
        'code' => 'rename',
        'label' => 'Soyadın dəyişdirilməsi',
        'effect' => 'surname_change',
        'docx_path' => 'order-templates/surname.docx',
        'variables' => [
            ['token' => 'var_1', 'label' => 'Yeni soyad', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_1', 'type' => 'text'], 'effect_role' => 'new_surname'],
        ],
        'is_active' => true,
    ]);

    test()->actingAs($user);
    Livewire::test(OrderComposer::class, ['presetCode' => 'rename', 'personnelId' => $personnel->id])
        ->set('orderNumber', '77-S')
        ->set('fields', ['var_1' => 'Əmrov'])
        ->call('issue');

    $order = OrderLog::query()->where('order_no', '77-S')->firstOrFail();
    $transitions = app(OrderStatusTransitionService::class);

    $transitions->approve($order);
    expect($personnel->fresh()->surname)->toBe('Əmrov');

    $transitions->cancel($order->fresh(), 'Test üçün geri alınır');
    expect($personnel->fresh()->surname)->toBe($original)
        ->and(app(GuardsPersonnelChanges::class)->isAllowed())->toBeFalse()
        ->and(changePolicyJournalEntries())->toHaveCount(0);
});

it('keeps an order effect working when its group is in journal mode, without a reason', function (): void {
    $user = changePolicyUser();
    $personnel = changePolicyPersonnel($user);
    changePolicySet('employment_dates', PersonnelChangeMode::Journal);

    app(GuardsPersonnelChanges::class)->allowForEffect('termination', fn () => $personnel->fresh()->forceFill(['leave_work_date' => '2025-03-31'])->save());

    expect($personnel->fresh()->leave_work_date->toDateString())->toBe('2025-03-31')
        ->and(changePolicyJournalEntries())->toHaveCount(0);
});

it('does not guard an employee whose hire is still pending approval', function (): void {
    $user = changePolicyUser();
    $personnel = changePolicyPersonnel($user);
    Personnel::withoutEvents(fn () => $personnel->forceFill(['is_pending' => true])->save());

    $personnel->fresh()->update(['join_work_date' => '2021-04-01', 'surname' => 'Gözləyən']);

    expect($personnel->fresh()->surname)->toBe('Gözləyən')
        ->and(PersonnelChangePolicy::query()->count())->toBe(0);
});
