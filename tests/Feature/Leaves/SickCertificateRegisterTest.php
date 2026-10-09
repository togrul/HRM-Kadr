<?php

use App\Enums\OrderStatusEnum;
use App\Models\AttendanceMonthlySummary;
use App\Models\Leave;
use App\Models\LeaveSickCertificate;
use App\Models\LeaveType;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\Setting;
use App\Models\User;
use App\Modules\Attendance\Domain\Contracts\PayrollAttendanceReadRepository;
use App\Modules\Leaves\Application\Services\SickCertificateRegister;
use App\Modules\Leaves\Application\Services\SickCertificateService;
use App\Modules\Leaves\Application\Services\SickCertificateSettings;
use App\Modules\Leaves\Contracts\SickCertificateAttention;
use App\Modules\Leaves\Exports\SickCertificateExport;
use App\Modules\Leaves\Livewire\SickCertificates\PersonnelSickCertificates;
use App\Modules\Leaves\Livewire\SickCertificates\SickCertificateEditor;
use App\Modules\Leaves\Livewire\SickCertificates\SickCertificates;
use App\Modules\Personnel\Application\Services\HomeOverviewService;
use App\Modules\Personnel\Application\Services\PersonnelPresenceResolver;
use App\Modules\Personnel\Livewire\PersonnelProfile;
use App\Modules\Personnel\Support\Presence\PersonnelPresenceStatus;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Xəstəlik vərəqələri reyestri: vərəqənin açılması/bağlanması/davamı/ləğvi, nömrə və tarix
 * nəzarəti, diaqnozun gizliliyi, iştirak statusu, ana səhifə xəbərdarlığı və əmək haqqına təsirsizlik.
 */
const SICK_TODAY = '2026-10-09';

const SICK_DIAGNOSIS = 'J06.9 kəskin respirator infeksiya';

beforeEach(function (): void {
    Carbon::setTestNow(SICK_TODAY.' 10:00:00');
    CarbonImmutable::setTestNow(SICK_TODAY.' 10:00:00');

    foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
        OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
    }

    sickPerson('SC-1', 'Əliyev');
    sickPerson('SC-2', 'Məmmədov');
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function sickPerson(string $tabelNo, string $surname): Personnel
{
    return Personnel::withoutEvents(fn (): Personnel => Personnel::query()->forceCreate([
        'tabel_no' => $tabelNo,
        'surname' => $surname,
        'name' => 'Test',
        'patronymic' => 'Ata',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'mobile' => '0501112233',
        'nationality_id' => 1,
        'pin' => strtoupper(substr(md5($tabelNo), 0, 7)),
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

/**
 * @param  list<string>  $permissions
 */
function sickUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    test()->actingAs($user);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function sickCertificate(array $overrides = [], ?User $actor = null): LeaveSickCertificate
{
    $actor ??= sickUser(['show-leaves', 'add-leaves', 'edit-leaves', 'view-medical-diagnosis']);

    return app(SickCertificateService::class)->open([
        'tabel_no' => 'SC-1',
        'series' => 'AB',
        'number' => '100001',
        'starts_at' => '2026-10-01',
        'ends_at' => null,
        'medical_institution' => 'Bakı şəhər 5 saylı poliklinika',
        'doctor_name' => 'Dr. Həsənova',
        'diagnosis' => SICK_DIAGNOSIS,
        'notes' => null,
        ...$overrides,
    ], $actor);
}

function sickErrors(Closure $callback): array
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

it('opens a certificate without an end date as an open, approved sick leave and closes it later', function (): void {
    $certificate = sickCertificate();
    $leave = Leave::query()->findOrFail($certificate->leave_id);

    expect($certificate->status)->toBe(LeaveSickCertificate::STATUS_OPEN)
        ->and($certificate->closed_at)->toBeNull()
        ->and($leave->ends_at)->toBeNull()
        ->and($leave->total_days)->toBeNull()
        ->and((int) $leave->status_id)->toBe(OrderStatusEnum::APPROVED->value)
        ->and($leave->reason)->not->toContain('J06')
        ->and(app(PersonnelPresenceResolver::class)->isSickType($leave->leaveType?->attendance_code, $leave->leaveType?->name))->toBeTrue();

    // Saving the leave for something unrelated keeps it open.
    $leave->refresh()->update(['document_path' => null]);
    expect($leave->refresh()->ends_at)->toBeNull();

    app(SickCertificateService::class)->close($certificate, '2026-10-07');

    $leave->refresh();
    expect($certificate->refresh()->status)->toBe(LeaveSickCertificate::STATUS_CLOSED)
        ->and($certificate->closed_at)->not->toBeNull()
        ->and($leave->ends_at->toDateString())->toBe('2026-10-07')
        ->and((int) $leave->total_days)->toBe(7);
});

it('reuses the existing sick leave type instead of creating a second one', function (): void {
    $existing = LeaveType::query()->create(['name' => 'Xəstəlik vərəqəsi', 'attendance_code' => 'XST', 'max_days' => 0]);

    $certificate = sickCertificate();

    expect((int) Leave::query()->findOrFail($certificate->leave_id)->leave_type_id)->toBe((int) $existing->id)
        ->and(LeaveType::query()->count())->toBe(1);
});

it('registers, edits and closes a certificate through the editor panel', function (): void {
    sickUser(['show-leaves', 'add-leaves', 'edit-leaves']);

    Livewire::test(SickCertificateEditor::class)
        ->call('openEditor', 'create')
        ->assertSet('open', true)
        ->set('personnelSearch', 'Əliy')
        ->call('chooseEmployee', 'SC-1')
        ->assertSet('employeeName', fn (string $name): bool => str_contains($name, 'Əliyev'))
        ->set('series', 'ab')
        ->set('number', '777')
        ->set('starts_at', '2026-10-05')
        ->set('medical_institution', 'Klinika')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('sick-certificates-changed')
        ->assertSet('open', false);

    $certificate = LeaveSickCertificate::query()->sole();
    expect($certificate->series)->toBe('AB')
        ->and($certificate->status)->toBe('open');

    Livewire::test(SickCertificateEditor::class)
        ->call('openEditor', 'edit', $certificate->id)
        ->assertSet('number', '777')
        ->set('doctor_name', 'Dr. Quliyev')
        ->call('save')
        ->assertHasNoErrors();

    expect($certificate->refresh()->doctor_name)->toBe('Dr. Quliyev');

    Livewire::test(SickCertificateEditor::class)
        ->call('openEditor', 'close', $certificate->id)
        ->assertSet('ends_at', SICK_TODAY)
        ->set('ends_at', '2026-10-08')
        ->call('save')
        ->assertHasNoErrors();

    expect($certificate->refresh()->status)->toBe('closed')
        ->and(Leave::query()->findOrFail($certificate->leave_id)->ends_at->toDateString())->toBe('2026-10-08');
});

it('needs add-leaves to register and edit-leaves to change a certificate', function (): void {
    $certificate = sickCertificate();
    sickUser(['show-leaves']);

    Livewire::test(SickCertificateEditor::class)->call('openEditor', 'create')->assertForbidden();
    Livewire::test(SickCertificateEditor::class)->call('openEditor', 'edit', $certificate->id)->assertForbidden();
});

it('refuses a duplicate series and number', function (): void {
    sickCertificate(['ends_at' => '2026-10-03']);

    $errors = sickErrors(fn () => sickCertificate(['tabel_no' => 'SC-2', 'series' => 'ab', 'starts_at' => '2026-10-05']));

    expect($errors)->toHaveKey('number')
        ->and($errors['number'][0])->toContain('AB-100001')
        ->and($errors['number'][0])->toContain('Əliyev');
});

it('blocks overlapping certificates for the same person, and an open one blocks any later start', function (): void {
    sickCertificate(['starts_at' => '2026-10-01', 'ends_at' => '2026-10-05']);

    expect(sickErrors(fn () => sickCertificate(['number' => '2', 'starts_at' => '2026-10-04', 'ends_at' => '2026-10-06'])))->toHaveKey('starts_at');

    // Another person on the same days is fine.
    sickCertificate(['tabel_no' => 'SC-2', 'number' => '3', 'starts_at' => '2026-10-04', 'ends_at' => '2026-10-06']);

    sickCertificate(['number' => '4', 'starts_at' => '2026-10-06', 'ends_at' => null]);

    $errors = sickErrors(fn () => sickCertificate(['number' => '5', 'starts_at' => '2026-10-20', 'ends_at' => '2026-10-22']));
    expect($errors)->toHaveKey('starts_at')
        ->and($errors['starts_at'][0])->toContain('AB-4');
});

it('warns about an overlapping vacation or business trip without blocking it', function (): void {
    DB::table('personnel_vacations')->insert([
        'tabel_no' => 'SC-1', 'vacation_places' => 'Bakı', 'duration' => 10, 'start_date' => '2026-10-01',
        'end_date' => '2026-10-10', 'return_work_date' => '2026-10-13', 'order_given_by' => 'HR', 'added_by' => 1,
    ]);
    DB::table('personnel_business_trips')->insert([
        'tabel_no' => 'SC-1', 'location' => 'Gəncə', 'start_date' => '2026-10-12', 'end_date' => '2026-10-14',
        'order_given_by' => 'HR', 'added_by' => 1,
    ]);

    $warnings = app(SickCertificateService::class)->warnings('SC-1', '2026-10-08', '2026-10-12');

    expect($warnings)->toHaveCount(2)
        ->and(implode(' ', $warnings))->toContain('01.10.2026')
        ->and(implode(' ', $warnings))->toContain('12.10.2026');

    sickUser(['show-leaves', 'add-leaves']);

    Livewire::test(SickCertificateEditor::class)
        ->call('openEditor', 'create')
        ->call('chooseEmployee', 'SC-1')
        ->set('number', '9')
        ->set('starts_at', '2026-10-08')
        ->set('ends_at', '2026-10-12')
        ->assertSee(__('leaves::sick_certificates.labels.overlaps_title'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    expect(LeaveSickCertificate::query()->count())->toBe(1);
});

it('extends an open certificate: the predecessor closes the day before the continuation', function (): void {
    $actor = sickUser(['show-leaves', 'add-leaves', 'edit-leaves']);
    $first = sickCertificate([], $actor);

    $errors = sickErrors(fn () => app(SickCertificateService::class)->extend($first, ['number' => '200', 'starts_at' => '2026-10-01'], $actor));
    expect($errors)->toHaveKey('starts_at');

    $next = app(SickCertificateService::class)->extend($first, ['series' => 'AB', 'number' => '200', 'starts_at' => '2026-10-06'], $actor);

    expect($first->refresh()->status)->toBe('closed')
        ->and(Leave::query()->findOrFail($first->leave_id)->ends_at->toDateString())->toBe('2026-10-05')
        ->and($next->continuation_of_id)->toBe($first->id)
        ->and($next->status)->toBe('open');
});

it('cancels a certificate together with its leave', function (): void {
    $actor = sickUser(['show-leaves', 'add-leaves', 'edit-leaves']);
    $certificate = sickCertificate([], $actor);

    expect(sickErrors(fn () => app(SickCertificateService::class)->cancel($certificate, '', $actor)))->toHaveKey('cancel_reason');

    Livewire::test(SickCertificates::class)
        ->call('cancelCertificate', $certificate->id, 'Səhv qeydiyyat')
        ->assertDispatched('sick-certificates-changed');

    $leave = Leave::query()->findOrFail($certificate->leave_id);
    expect($certificate->refresh()->status)->toBe('cancelled')
        ->and($certificate->cancel_reason)->toBe('Səhv qeydiyyat')
        ->and((int) $leave->status_id)->toBe(OrderStatusEnum::CANCELLED->value);

    // A cancelled certificate frees its number's days for a new one but cannot be edited.
    expect(sickErrors(fn () => app(SickCertificateService::class)->close($certificate, '2026-10-05')))->toHaveKey('number');
    sickCertificate(['number' => '100002'], $actor);
});

it('counts an open certificate as sick today and a cancelled one not at all', function (): void {
    $actor = sickUser(['show-leaves', 'add-leaves', 'edit-leaves']);
    $open = sickCertificate(['starts_at' => '2026-09-20'], $actor);
    $cancelled = sickCertificate(['tabel_no' => 'SC-2', 'number' => '2', 'starts_at' => '2026-10-01'], $actor);
    app(SickCertificateService::class)->cancel($cancelled, 'Səhv qeydiyyat', $actor);

    $resolver = app(PersonnelPresenceResolver::class);
    $presence = $resolver->resolveIds(Personnel::query()->pluck('id')->all());
    $first = Personnel::query()->where('tabel_no', 'SC-1')->value('id');
    $second = Personnel::query()->where('tabel_no', 'SC-2')->value('id');

    expect($presence[$first]->status)->toBe(PersonnelPresenceStatus::Sick)
        ->and($presence[$first]->periodStart->toDateString())->toBe('2026-09-20')
        ->and($presence[$first]->periodEnd)->toBeNull()
        ->and($presence[$first]->expectedReturn)->toBeNull()
        ->and($presence[$second]->status)->toBe(PersonnelPresenceStatus::AtWork);

    // The SQL side agrees (list filter / home counts).
    $counts = $resolver->countByStatus(Personnel::query(), [PersonnelPresenceStatus::Sick, PersonnelPresenceStatus::AtWork]);
    expect($counts)->toBe(['sick' => 1, 'at_work' => 1]);

    // Once closed in the past, the person is back at work.
    app(SickCertificateService::class)->close($open, '2026-10-02');
    expect($resolver->resolveIds([$first])[$first]->status)->toBe(PersonnelPresenceStatus::AtWork);
});

it('keeps the diagnosis encrypted, out of arrays, lists, exports and the activity log', function (): void {
    $certificate = sickCertificate();

    $raw = DB::table('leave_sick_certificates')->where('id', $certificate->id)->value('diagnosis');
    expect($raw)->not->toBeNull()
        ->and($raw)->not->toContain('J06')
        ->and($certificate->refresh()->diagnosis)->toBe(SICK_DIAGNOSIS)
        ->and($certificate->toArray())->not->toHaveKey('diagnosis')
        ->and($certificate->toJson())->not->toContain('J06');

    $logged = Activity::query()->where('subject_type', LeaveSickCertificate::class)->get();
    expect($logged)->not->toBeEmpty()
        ->and($logged->pluck('properties')->toJson())->not->toContain('J06')
        ->and($logged->pluck('properties')->toJson())->not->toContain('diagnosis')
        ->and(Activity::query()->get()->pluck('properties')->toJson())->not->toContain('J06');

    // Even a diagnosis holder never sees it in the register or the employee card list.
    sickUser(['show-leaves', 'add-leaves', 'edit-leaves', 'export-leaves', 'view-medical-diagnosis']);
    Livewire::test(SickCertificates::class)->assertSee('100001')->assertDontSee('J06');
    Livewire::test(PersonnelSickCertificates::class, ['tabelNo' => 'SC-1'])->assertSee('100001')->assertDontSee('J06');

    $rows = app(SickCertificateRegister::class)->listing()->get();
    expect($rows->first()->getAttributes())->not->toHaveKey('diagnosis');

    $sheet = (new SickCertificateExport($rows))->view()->render();
    expect($sheet)->toContain('100001')->not->toContain('J06');

    Livewire::test(SickCertificates::class)->call('exportExcel')->assertFileDownloaded();
});

it('shows and writes the diagnosis only with view-medical-diagnosis', function (): void {
    $certificate = sickCertificate();

    sickUser(['show-leaves', 'add-leaves', 'edit-leaves']);
    Livewire::test(SickCertificateEditor::class)
        ->call('openEditor', 'edit', $certificate->id)
        ->assertSet('diagnosis', '')
        ->assertDontSee(__('leaves::sick_certificates.labels.diagnosis'))
        ->set('diagnosis', 'overwritten')
        ->set('notes', 'Yeni qeyd')
        ->call('save')
        ->assertHasNoErrors();

    expect($certificate->refresh()->diagnosis)->toBe(SICK_DIAGNOSIS)
        ->and($certificate->notes)->toBe('Yeni qeyd');

    // Registering without the permission stores no diagnosis even if one is sent.
    $plain = sickCertificate(['tabel_no' => 'SC-2', 'number' => '5'], User::query()->latest('id')->first());
    expect($plain->refresh()->diagnosis)->toBeNull();

    sickUser(['show-leaves', 'edit-leaves', 'view-medical-diagnosis']);
    Livewire::test(SickCertificateEditor::class)
        ->call('openEditor', 'edit', $certificate->id)
        ->assertSet('diagnosis', SICK_DIAGNOSIS)
        ->assertSee(__('leaves::sick_certificates.labels.diagnosis'));
});

it('filters the register and shows the header figures', function (): void {
    $actor = sickUser(['show-leaves', 'add-leaves', 'edit-leaves']);
    sickCertificate(['starts_at' => '2026-08-01', 'ends_at' => '2026-08-05', 'medical_institution' => 'Mərkəzi xəstəxana'], $actor);
    sickCertificate(['number' => '2', 'starts_at' => '2026-10-07'], $actor);
    sickCertificate(['tabel_no' => 'SC-2', 'number' => '3', 'starts_at' => '2026-08-01'], $actor);

    $component = Livewire::test(SickCertificates::class);
    expect($component->instance()->stats())->toBe(['total' => 3, 'open' => 2, 'closed' => 1, 'cancelled' => 0, 'days' => 5 + 3 + 70]);

    $component->set('status', 'closed')->assertSee('100001')->assertDontSee('№ AB-2');
    $component->set('status', '')->set('institution', 'Mərkəzi')->assertSee('100001')->assertDontSee('№ AB-3');
    $component->call('resetFilter')->set('number', '3')->assertSee('№ AB-3')->assertDontSee('100001');
    $component->call('resetFilter')->set('stale', true)->assertSee('№ AB-3')->assertDontSee('№ AB-2');
    $component->call('resetFilter')->set('from', '2026-10-01')->set('to', '2026-10-31')->assertSee('№ AB-2')->assertSee('№ AB-3')->assertDontSee('100001');
});

it('lists the person\'s certificates in the employee card\'s «Xəstəlik» section', function (): void {
    $actor = sickUser(['show-personnels', 'show-leaves', 'add-leaves', 'edit-leaves']);
    sickCertificate([], $actor);
    sickCertificate(['tabel_no' => 'SC-2', 'number' => '2'], $actor);

    $person = Personnel::query()->where('tabel_no', 'SC-1')->firstOrFail();
    $this->mock(\App\Services\StructureService::class, fn ($mock) => $mock->shouldReceive('getAccessibleStructures')->andReturn([1]));

    Livewire::withQueryParams(['section' => 'sick'])
        ->test(PersonnelProfile::class, ['personnel' => $person])
        ->assertSet('section', 'sick')
        ->assertSeeLivewire(PersonnelSickCertificates::class);

    Livewire::test(PersonnelSickCertificates::class, ['tabelNo' => 'SC-1'])
        ->assertSee('100001')
        ->assertDontSee('№ AB-2')
        ->assertSee(__('leaves::sick_certificates.actions.new'));
});

it('hides the employee card section without show-leaves', function (): void {
    sickUser(['show-personnels', 'edit-personnels']);
    $person = Personnel::query()->where('tabel_no', 'SC-1')->firstOrFail();
    $this->mock(\App\Services\StructureService::class, fn ($mock) => $mock->shouldReceive('getAccessibleStructures')->andReturn([1]));

    Livewire::withQueryParams(['section' => 'sick'])
        ->test(PersonnelProfile::class, ['personnel' => $person])
        ->assertSet('section', 'overview')
        ->call('setSection', 'sick')
        ->assertSet('section', 'overview');
});

it('flags certificates open past the threshold on the home attention panel', function (): void {
    $actor = sickUser(['show-leaves', 'add-leaves']);
    sickCertificate(['starts_at' => '2026-08-20'], $actor);
    sickCertificate(['tabel_no' => 'SC-2', 'number' => '2', 'starts_at' => '2026-10-01'], $actor);
    sickCertificate(['number' => '3', 'starts_at' => '2026-07-01', 'ends_at' => '2026-07-05'], $actor);

    expect(app(SickCertificateAttention::class)->staleOpen())->toBe(['count' => 1, 'oldest_days' => 50, 'threshold_days' => 30]);

    $tile = collect(app(HomeOverviewService::class)->attention($actor))->firstWhere('key', 'stale_sick_certificates');
    expect($tile)->not->toBeNull()
        ->and($tile['count'])->toBe(1)
        ->and($tile['route'])->toBe('leaves.sick-certificates')
        ->and($tile['params'])->toBe(['stale' => 1]);

    Setting::query()->create(['name' => SickCertificateSettings::SETTING, 'value' => '5', 'type' => 'int']);
    expect(app(SickCertificateAttention::class)->staleOpen()['count'])->toBe(2);

    $viewer = sickUser(['show-orders']);
    expect(collect(app(HomeOverviewService::class)->attention($viewer))->pluck('key'))->not->toContain('stale_sick_certificates');
});

it('leaves payroll proration alone: sick days are not unpaid absence', function (): void {
    $actor = sickUser(['show-leaves', 'add-leaves']);
    sickCertificate(['starts_at' => '2026-09-01', 'ends_at' => '2026-09-10'], $actor);
    sickCertificate(['tabel_no' => 'SC-1', 'number' => '2', 'starts_at' => '2026-09-20'], $actor);

    AttendanceMonthlySummary::query()->forceCreate([
        'tabel_no' => 'SC-1', 'year' => 2026, 'month' => 9, 'total_workdays' => 22, 'total_absence_days' => 0,
    ]);

    expect(app(PayrollAttendanceReadRepository::class)->monthlyAbsence('SC-1', 2026, 9))
        ->toBe(['working_days' => 22, 'absence_days' => 0]);
});

it('grants view-medical-diagnosis to Admin and HR Admin on existing installs', function (): void {
    foreach (['Admin', 'HR Admin', 'HR Employee'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    $migration = require base_path('app/Modules/Leaves/Database/Migrations/2026_10_09_200100_add_view_medical_diagnosis_permission.php');
    $migration->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(Role::findByName('Admin', 'web')->hasPermissionTo('view-medical-diagnosis'))->toBeTrue()
        ->and(Role::findByName('HR Admin', 'web')->hasPermissionTo('view-medical-diagnosis'))->toBeTrue()
        ->and(Role::findByName('HR Employee', 'web')->hasPermissionTo('view-medical-diagnosis'))->toBeFalse();
});

it('renders the register page and links it from the leaves page navigation', function (): void {
    $actor = sickUser(['show-leaves', 'add-leaves', 'export-leaves']);
    sickCertificate([], $actor);

    $this->get(route('leaves.sick-certificates'))
        ->assertOk()
        ->assertSee(__('leaves::sick_certificates.title'))
        ->assertSee('AB-100001')
        ->assertDontSee('J06');

    $this->get(route('leaves'))
        ->assertOk()
        ->assertSee(route('leaves.sick-certificates'), false);

    sickUser(['add-leaves']);
    $this->get(route('leaves.sick-certificates'))->assertForbidden();
});

/**
 * @return array<string, string> date => attendance_status of the person's ledger rows
 */
function sickLedger(string $tabelNo = 'SC-1'): array
{
    return DB::table('attendance_daily_ledgers')
        ->where('tabel_no', $tabelNo)
        ->orderBy('date')
        ->get(['date', 'attendance_status'])
        ->mapWithKeys(fn ($row): array => [substr((string) $row->date, 0, 10) => (string) $row->attendance_status])
        ->all();
}

function forgetAttendanceLocks(): void
{
    (new ReflectionProperty(\App\Modules\Attendance\Application\Services\AttendanceMonthLockService::class, 'periodLockMemo'))->setValue(null, []);
}

it('puts an open certificate into attendance from its start up to today, and keeps extending it on read', function (): void {
    forgetAttendanceLocks();
    sickCertificate(['starts_at' => '2026-10-05']);

    expect(sickLedger())->toBe([
        '2026-10-05' => 'leave', '2026-10-06' => 'leave', '2026-10-07' => 'leave', '2026-10-08' => 'leave', '2026-10-09' => 'leave',
    ]);

    // Two days later, with no new ledger rows yet, the puantaj read still sees the sick days up to today.
    Carbon::setTestNow('2026-10-11 10:00:00');
    CarbonImmutable::setTestNow('2026-10-11 10:00:00');

    $context = app(\App\Modules\Attendance\Application\Services\AttendanceDayContextResolverService::class)
        ->build(Carbon::parse('2026-10-09'), Carbon::parse('2026-10-14'), collect(['SC-1']));

    expect(array_keys(array_filter($context['overrides'], fn (array $override): bool => ($override['type'] ?? null) === 'leave')))
        ->toBe(['SC-1|2026-10-09', 'SC-1|2026-10-10', 'SC-1|2026-10-11']);
});

it('replaces the open range with the final one on close and removes it on cancel', function (): void {
    forgetAttendanceLocks();
    $actor = sickUser(['show-leaves', 'add-leaves', 'edit-leaves']);
    $certificate = sickCertificate(['starts_at' => '2026-10-05'], $actor);

    app(SickCertificateService::class)->close($certificate, '2026-10-07');

    expect(collect(sickLedger())->filter(fn (string $status): bool => $status === 'leave')->keys()->all())
        ->toBe(['2026-10-05', '2026-10-06', '2026-10-07']);

    app(SickCertificateService::class)->cancel($certificate, 'Səhv qeydiyyat', $actor);

    expect(collect(sickLedger())->filter(fn (string $status): bool => $status === 'leave')->all())->toBe([]);
});

it('does not rewrite a locked attendance month', function (): void {
    forgetAttendanceLocks();
    AttendanceMonthlySummary::query()->forceCreate(['tabel_no' => 'SC-1', 'year' => 2026, 'month' => 9, 'is_locked' => true]);
    DB::table('attendance_daily_ledgers')->insert([
        'tabel_no' => 'SC-1', 'date' => '2026-09-29', 'attendance_status' => 'present', 'is_locked' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    sickCertificate(['starts_at' => '2026-09-28']);

    $ledger = sickLedger();
    expect($ledger['2026-09-29'])->toBe('present')
        ->and($ledger)->not->toHaveKey('2026-09-28')
        ->and($ledger['2026-10-01'])->toBe('leave')
        ->and($ledger['2026-10-09'])->toBe('leave');
});

it('keeps certificate leaves read-only in the general leaves list and every leave edit path', function (): void {
    $user = sickUser(['show-leaves', 'add-leaves', 'edit-leaves', 'delete-leaves', 'view-medical-diagnosis']);
    $certificate = sickCertificate([], $user);
    $leave = Leave::query()->findOrFail($certificate->leave_id);

    expect($user->can('update', $leave))->toBeFalse()
        ->and($user->can('delete', $leave))->toBeFalse()
        ->and($user->can('forceDelete', $leave))->toBeFalse();

    Livewire::test(\App\Modules\Leaves\Livewire\Leaves::class)
        ->assertSee(__('leaves::common.labels.sick_certificate'))
        ->assertSee(route('leaves.sick-certificates', ['number' => '100001']), false)
        ->assertDontSee('openEditLeaveModal('.$leave->id.')', false)
        ->assertDontSee("setDeleteLeave('".$leave->id."')", false);

    Livewire::test(\App\Modules\Leaves\Livewire\EditLeave::class)->call('loadLeaveForEdit', $leave->id)->assertForbidden();
    Livewire::test(\App\Modules\Leaves\Livewire\DeleteLeave::class)->call('setDeleteLeave', $leave->id)->assertForbidden();
    Livewire::test(\App\Modules\Leaves\Livewire\Leaves::class)->call('forceDeleteData', $leave->id)->assertForbidden();

    expect(sickErrors(fn () => app(\App\Modules\Leaves\Application\Services\LeaveRecordService::class)->update($leave, ['status_id' => 20], $user)))
        ->toHaveKey('starts_at');

    // Self-service: no correction offered, and a forged request is refused.
    $person = Personnel::query()->where('tabel_no', 'SC-1')->firstOrFail();
    expect(sickErrors(fn () => app(\App\Modules\Personnel\Application\Services\MyHr\MyHrRequestCorrectionService::class)
        ->create($person, $user, 'leave', $leave->id, 'Tarix səhvdir', ['ends_at' => '2026-10-03'])))->toHaveKey('correctionForm.reason');
    expect(sickErrors(fn () => app(\App\Modules\Personnel\Application\Services\MyHr\Review\SelfServiceRequestPatchService::class)
        ->apply($leave, ['ends_at' => '2026-10-03'])))->toHaveKey('reason');

    expect(Leave::query()->findOrFail($leave->id)->ends_at)->toBeNull()
        ->and(LeaveSickCertificate::query()->count())->toBe(1);

    // An ordinary leave is still editable.
    $plain = Leave::query()->create([
        'tabel_no' => 'SC-2', 'leave_type_id' => LeaveType::query()->create(['name' => 'Ödənişsiz', 'max_days' => 0])->id,
        'starts_at' => '2026-10-20', 'ends_at' => '2026-10-21', 'status_id' => 10,
    ]);
    expect($user->can('update', $plain))->toBeTrue();
});

it('shows the stale-certificate setting with a translated label', function (): void {
    sickUser(['access-settings']);
    app()->setLocale('az');

    $label = Livewire::test(\App\Modules\Services\Livewire\Settings\SettingsList::class, ['section' => 'general'])
        ->instance()
        ->resolveSettingLabel(SickCertificateSettings::SETTING);

    expect($label)->toBe('Açıq xəstəlik vərəqəsi üçün xəbərdarlıq həddi (gün)');
});
