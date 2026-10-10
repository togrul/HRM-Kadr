<?php

use App\Models\OrderLog;
use App\Models\OrderParticipant;
use App\Models\OrderStatus;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Application\Services\OrderVisibilityService;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderSubjectResolver;
use App\Modules\Orders\Livewire\AllOrders;
use App\Modules\Orders\Livewire\OrderComposer;
use App\Modules\Orders\Livewire\OrderPreview;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\Permission\Models\Permission;

/**
 * Əmrlərin struktur görünürlüyü: siyahı, siyahıdakı hər əməliyyat, policy, önizləmə və
 * əmr tərtibçisi eyni qaydanı işlədir — əmrin bütün işçiləri (və ya işə qəbul hədəf
 * strukturu) istifadəçinin strukturlarında olmalıdır. Struktursuz rol heç nə görmür.
 */
beforeEach(function (): void {
    Storage::fake('local');

    foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
        OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
    }

    $this->inside = Structure::query()->create(['name' => 'Daxili', 'shortname' => 'D']);
    $this->outside = Structure::query()->create(['name' => 'Xarici', 'shortname' => 'X']);

    $this->insidePerson = scopeOrdersPersonnel('Daxiliyev', $this->inside->id);
    $this->outsidePerson = scopeOrdersPersonnel('Xariciyev', $this->outside->id);

    $this->insideOrder = scopeOrdersOrder('IN-1', 10, [$this->insidePerson]);
    $this->outsideOrder = scopeOrdersOrder('OUT-1', 10, [$this->outsidePerson]);
    $this->mixedOrder = scopeOrdersOrder('MIX-1', 10, [$this->insidePerson, $this->outsidePerson]);
    $this->insideHire = scopeOrdersOrder('HIRE-IN', 10, [], $this->inside->id);
    $this->outsideHire = scopeOrdersOrder('HIRE-OUT', 10, [], $this->outside->id);
});

function scopeOrdersPersonnel(string $surname, int $structureId): Personnel
{
    $position = Position::query()->firstOrCreate(['name' => 'operator']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'SC'.Str::upper(Str::random(6)),
        'surname' => $surname,
        'name' => 'Test',
        'patronymic' => 'Test',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'email' => Str::lower(Str::random(8)).'@example.com',
        'mobile' => '994501112233',
        'nationality_id' => 1,
        'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
        'residental_address' => 'Main st',
        'education_degree_id' => 1,
        'work_norm_id' => 1,
        'structure_id' => $structureId,
        'position_id' => $position->id,
        'join_work_date' => '2020-01-01',
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

/**
 * @param  list<Personnel>  $people
 */
function scopeOrdersOrder(string $no, int $status, array $people, ?int $hireStructureId = null): OrderLog
{
    $order = OrderLog::query()->create([
        'order_id' => null,
        'order_no' => $no,
        'given_date' => now(),
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => $status,
        'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
        'template_snapshot' => [
            'template_code' => $hireStructureId ? 'ise_qebul' : 'leave',
            'label' => 'Test',
            'fields' => [],
            'personnel_id' => count($people) === 1 ? $people[0]->id : null,
            'hire_structure_id' => $hireStructureId,
            'order_date_text' => '14.05.2026-cı il',
            'docx_path' => 'order-documents/'.$no.'.docx',
        ],
    ]);

    foreach ($people as $person) {
        $order->personnels()->attach($person->tabel_no);
    }

    Storage::disk('local')->put('order-documents/'.$no.'.docx', 'docx');

    return $order;
}

/**
 * @param  list<string>  $permissions
 */
function scopeOrdersUser(array $permissions = ['show-orders', 'add-orders', 'edit-orders', 'delete-orders', 'export-orders', 'revert-orders']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

it('lists only orders whose personnel (or hire target) are all within scope', function (): void {
    $this->actingAs(grantStructures(scopeOrdersUser(), [$this->inside->id]));

    Livewire::test(AllOrders::class)
        ->call('setStatus', 'all')
        ->assertSee('IN-1')
        ->assertSee('HIRE-IN')
        ->assertDontSee('OUT-1')
        ->assertDontSee('MIX-1')
        ->assertDontSee('HIRE-OUT');
});

it('shows nothing to a role without structures and everything to an all-structures role', function (): void {
    $this->actingAs(scopeOrdersUser());

    Livewire::test(AllOrders::class)
        ->call('setStatus', 'all')
        ->assertDontSee('IN-1')
        ->assertDontSee('HIRE-IN');

    $this->actingAs(grantAllStructures(scopeOrdersUser()));

    Livewire::test(AllOrders::class)
        ->call('setStatus', 'all')
        ->assertSee('IN-1')
        ->assertSee('OUT-1')
        ->assertSee('MIX-1')
        ->assertSee('HIRE-OUT');
});

it('refuses every list action on an out-of-scope order', function (): void {
    $user = grantStructures(scopeOrdersUser(), [$this->inside->id]);
    $this->actingAs($user);

    $approved = scopeOrdersOrder('OUT-APPROVED', 20, [$this->outsidePerson]);

    // Struktur xaricindəki əmr «tapılmadı» kimi qəbul edilir: heç bir hadisə, heç bir dəyişiklik.
    $silent = fn ($component) => $component
        ->assertNotDispatched('orderAdded')
        ->assertNotDispatched('orderError')
        ->assertNotDispatched('orderWasDeleted');

    $silent(Livewire::test(AllOrders::class)->call('approveOrder', 'OUT-1'));
    $silent(Livewire::test(AllOrders::class)->call('cancelOrder', 'OUT-1', 'səbəb'));
    $silent(Livewire::test(AllOrders::class)->call('revertOrder', 'OUT-APPROVED', 'səbəb'));
    $silent(Livewire::test(AllOrders::class)->call('reopenOrder', 'OUT-1'));
    $silent(Livewire::test(AllOrders::class)->call('duplicateOrder', 'OUT-1'));
    $silent(Livewire::test(AllOrders::class)->call('deleteOrder', 'OUT-1'));
    Livewire::test(AllOrders::class)->call('printOrder', 'OUT-APPROVED')->assertNotFound();
    Livewire::test(AllOrders::class)->call('downloadPdf', 'OUT-APPROVED')->assertNotFound();

    expect((int) $this->outsideOrder->fresh()->status_id)->toBe(10)
        ->and((int) $approved->fresh()->status_id)->toBe(20)
        ->and($this->outsideOrder->fresh()->trashed())->toBeFalse()
        ->and(OrderLog::query()->where('order_no', 'like', 'OUT-1-kopya%')->exists())->toBeFalse();

    $this->outsideOrder->delete();
    $silent(Livewire::test(AllOrders::class)->call('restoreData', 'OUT-1'));
    expect($this->outsideOrder->fresh()->trashed())->toBeTrue();

    $silent(Livewire::test(AllOrders::class)->call('forceDeleteData', 'OUT-1'));
    expect(OrderLog::withTrashed()->whereKey($this->outsideOrder->id)->exists())->toBeTrue();
});

it('still runs list actions on an in-scope order', function (): void {
    $this->actingAs(grantStructures(scopeOrdersUser(), [$this->inside->id]));

    Livewire::test(AllOrders::class)
        ->call('printOrder', 'IN-1')
        ->assertFileDownloaded('IN-1.docx');

    Livewire::test(AllOrders::class)->call('deleteOrder', 'IN-1')->assertDispatched('orderWasDeleted');
    expect($this->insideOrder->fresh()->trashed())->toBeTrue();
});

it('applies the scope in the order policy matrix', function (): void {
    $limited = grantStructures(scopeOrdersUser(), [$this->inside->id]);
    $none = scopeOrdersUser();
    $all = grantAllStructures(scopeOrdersUser());
    $noPermission = grantAllStructures(User::factory()->create());

    foreach (['view', 'update', 'delete', 'restore', 'forceDelete', 'transition', 'download'] as $ability) {
        expect(Gate::forUser($limited)->allows($ability, $this->insideOrder))->toBeTrue("limited {$ability} in-scope")
            ->and(Gate::forUser($limited)->allows($ability, $this->insideHire))->toBeTrue("limited {$ability} in-scope hire")
            ->and(Gate::forUser($limited)->allows($ability, $this->outsideOrder))->toBeFalse("limited {$ability} out-of-scope")
            ->and(Gate::forUser($limited)->allows($ability, $this->mixedOrder))->toBeFalse("limited {$ability} mixed")
            ->and(Gate::forUser($limited)->allows($ability, $this->outsideHire))->toBeFalse("limited {$ability} out-of-scope hire")
            ->and(Gate::forUser($none)->allows($ability, $this->insideOrder))->toBeFalse("none {$ability}")
            ->and(Gate::forUser($all)->allows($ability, $this->outsideOrder))->toBeTrue("all {$ability}")
            ->and(Gate::forUser($noPermission)->allows($ability, $this->insideOrder))->toBeFalse("no permission {$ability}");
    }

    $approved = scopeOrdersOrder('OUT-REV', 20, [$this->outsidePerson]);
    expect(Gate::forUser($limited)->allows('revert', $approved))->toBeFalse()
        ->and(Gate::forUser($all)->allows('revert', $approved))->toBeTrue();
});

it('treats a multi-participant order as in scope only when every participant is', function (): void {
    $multi = scopeOrdersOrder('MULTI-1', 10, []);
    OrderParticipant::query()->create(['order_log_id' => $multi->id, 'personnel_id' => $this->insidePerson->id, 'position' => 1]);
    OrderParticipant::query()->create(['order_log_id' => $multi->id, 'personnel_id' => $this->outsidePerson->id, 'position' => 2]);

    $limited = grantStructures(scopeOrdersUser(), [$this->inside->id]);
    $visibility = app(OrderVisibilityService::class);

    expect($visibility->canSee($limited, $multi))->toBeFalse();

    OrderParticipant::query()->where('personnel_id', $this->outsidePerson->id)->delete();
    expect($visibility->canSee($limited, $multi))->toBeTrue();
});

it('forbids previewing an out-of-scope order', function (): void {
    $this->actingAs(grantStructures(scopeOrdersUser(), [$this->inside->id]));

    Livewire::test(OrderPreview::class, ['orderId' => $this->outsideOrder->id])->assertForbidden();
    Livewire::test(OrderPreview::class, ['orderId' => $this->insideOrder->id])->assertOk();
});

it('forbids reopening an out-of-scope order in the composer', function (): void {
    $this->actingAs(grantStructures(scopeOrdersUser(), [$this->inside->id]));

    Livewire::test(OrderComposer::class, ['orderId' => $this->outsideOrder->id])->assertForbidden();
});

it('limits the composer personnel search and picks to the user\'s structures', function (): void {
    $this->actingAs(grantStructures(scopeOrdersUser(), [$this->inside->id]));
    $subjects = app(OrderSubjectResolver::class);

    $labels = array_column($subjects->searchPersonnel('iyev'), 'label');

    expect(implode(' ', $labels))->toContain('Daxiliyev')->not->toContain('Xariciyev')
        ->and($subjects->personnelPick($this->outsidePerson->id))->toBeNull()
        ->and($subjects->personnelPick($this->insidePerson->id))->not->toBeNull();

    // Dərin keçiddən gələn struktur xaricindəki işçi seçilmir.
    seedScopeOrdersTemplate();
    Livewire::test(OrderComposer::class, ['presetCode' => 'leave', 'personnelId' => $this->outsidePerson->id])
        ->assertSet('personnelId', null);
});

it('rejects out-of-scope subjects and hire structures on issue', function (): void {
    $user = grantStructures(scopeOrdersUser(), [$this->inside->id]);
    $this->actingAs($user);
    seedScopeOrdersTemplate();

    $subjects = app(OrderSubjectResolver::class);
    $composition = fn (array $overrides): OrderComposition => new OrderComposition(...array_merge([
        'presetCode' => 'leave',
        'personnelId' => null,
        'candidateId' => null,
        'hireStructureId' => null,
        'hirePositionId' => null,
        'fields' => [],
        'orderNumber' => 'X-1',
        'orderDate' => '14.05.2026',
        'organizationCity' => 'Bakı',
    ], $overrides));

    expect($subjects->scopeErrors($composition(['personnelId' => $this->outsidePerson->id])))->toHaveKey('personnelId')
        ->and($subjects->scopeErrors($composition(['participants' => [['personnel_id' => $this->insidePerson->id], ['personnel_id' => $this->outsidePerson->id]]])))->toHaveKey('participants')
        ->and($subjects->scopeErrors($composition(['hireStructureId' => $this->outside->id])))->toHaveKey('hireStructureId')
        ->and($subjects->scopeErrors($composition(['personnelId' => $this->insidePerson->id, 'hireStructureId' => $this->inside->id])))->toBe([]);

    $before = OrderLog::query()->count();

    Livewire::test(OrderComposer::class, ['presetCode' => 'leave', 'personnelId' => $this->insidePerson->id])
        ->set('hireStructureId', $this->outside->id)
        ->set('orderNumber', 'HACK-1')
        ->set('orderDate', '2026-05-14')
        ->set('fields', ['var_2' => '19.05.2026'])
        ->call('issue')
        ->assertHasErrors('hireStructureId');

    expect(OrderLog::query()->count())->toBe($before);
});

function seedScopeOrdersTemplate(): void
{
    $relative = 'order-templates/leave.docx';
    $phpWord = new PhpWord;
    $phpWord->addSection()->addText('Əmr: ${var_1} işçisinə ${var_2} tarixindən məzuniyyət verilsin.');
    $tmp = tempnam(sys_get_temp_dir(), 'mst_').'.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($tmp);
    Storage::disk('local')->put($relative, (string) file_get_contents($tmp));
    @unlink($tmp);

    OrderWordTemplate::query()->firstOrCreate(['code' => 'leave'], [
        'label' => 'Məzuniyyət',
        'docx_path' => $relative,
        'variables' => [
            ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name_dative', 'field' => null],
            ['token' => 'var_2', 'label' => 'Başlama tarixi', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'text']],
        ],
        'is_active' => true,
    ]);
}
