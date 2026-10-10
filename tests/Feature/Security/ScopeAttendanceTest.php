<?php

use App\Models\AttendanceManualEntry;
use App\Models\AttendanceOvertimeRequest;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use App\Modules\Attendance\Livewire\ExceptionsInbox;
use App\Modules\Attendance\Livewire\ManualEntries;
use App\Modules\Attendance\Livewire\MonthClose;
use App\Modules\Attendance\Livewire\OvertimeBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * Davamiyyət: icazə bayraqları yalnız göstəriş üçündür (kilidli), hər əməliyyat icazəni
 * yenidən hesablayır; siyahılar və yazma əməliyyatları struktur görünürlüyü ilə məhdudlaşır.
 */
function scopeAttSeed(): void
{
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');

    DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
    DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
    DB::table('structures')->insertOrIgnore([
        ['id' => 1, 'name' => 'Daxili struktur', 'shortname' => 'DS'],
        ['id' => 2, 'name' => 'Kənar struktur', 'shortname' => 'KS'],
    ]);
    DB::table('positions')->insertOrIgnore(['id' => 1, 'name' => 'Analitik']);
    DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Full']);
}

function scopeAttPersonnel(string $tabelNo, int $structureId, string $surname): Personnel
{
    $addedBy = User::query()->value('id') ?? User::factory()->create()->id;

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => $tabelNo,
        'surname' => $surname,
        'name' => 'Test',
        'patronymic' => 'Test',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'mobile' => '0500000000',
        'nationality_id' => 1,
        'pin' => substr(str_replace('-', '', $tabelNo).'12345', 0, 7),
        'residental_address' => 'Bakı',
        'education_degree_id' => 1,
        'structure_id' => $structureId,
        'position_id' => 1,
        'work_norm_id' => 1,
        'join_work_date' => '2024-01-01',
        'added_by' => $addedBy,
        'is_pending' => false,
    ]));
}

function scopeAttOvertime(string $tabelNo, User $by): AttendanceOvertimeRequest
{
    return Schema::withoutForeignKeyConstraints(fn () => AttendanceOvertimeRequest::query()->create([
        'tabel_no' => $tabelNo,
        'date' => now()->toDateString(),
        'requested_minutes' => 60,
        'status' => 'pending',
        'source' => 'manual',
        'requested_by' => $by->id,
    ]));
}

/** @param  list<string>  $permissions */
function scopeAttUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

function scopeAttBoard(): array
{
    return ['year' => (int) now()->year, 'month' => (int) now()->month];
}

beforeEach(function (): void {
    scopeAttSeed();
});

it('locks the permission flags so the client cannot grant itself an action', function (string $component, array $params, string $flag): void {
    $this->actingAs(grantAllStructures(scopeAttUser(['show-attendance', 'show-attendance-month-close'])));

    expect(fn () => Livewire::test($component, $params)->set($flag, true))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    'overtime approve' => [OvertimeBoard::class, ['year' => 2026, 'month' => 3], 'canApprove'],
    'overtime create' => [OvertimeBoard::class, ['year' => 2026, 'month' => 3], 'canCreate'],
    'manual write' => [ManualEntries::class, [], 'canWrite'],
    'manual approve' => [ManualEntries::class, [], 'canApprove'],
    'exceptions resolve' => [ExceptionsInbox::class, ['year' => 2026, 'month' => 3], 'canResolve'],
    'month manage' => [MonthClose::class, ['year' => 2026, 'month' => 3], 'canManage'],
]);

it('refuses an overtime approval from a user without the approve permission', function (): void {
    $viewer = grantAllStructures(scopeAttUser(['show-attendance']));
    scopeAttPersonnel('SA-001', 1, 'Daxili');
    $request = scopeAttOvertime('SA-001', $viewer);
    $this->actingAs($viewer);

    Livewire::test(OvertimeBoard::class, scopeAttBoard())
        ->call('approve', $request->id)
        ->assertForbidden();

    expect($request->fresh()->status)->toBe('pending');
});

it('refuses to approve an overtime request of an employee outside the approver\'s structures', function (): void {
    $approver = grantStructures(scopeAttUser(['approve-attendance-overtime']), [1]);
    scopeAttPersonnel('SA-OUT', 2, 'Kənar');
    $request = scopeAttOvertime('SA-OUT', $approver);
    $this->actingAs($approver);

    Livewire::test(OvertimeBoard::class, scopeAttBoard())
        ->call('approve', $request->id)
        ->assertForbidden();

    expect($request->fresh()->status)->toBe('pending');
});

it('refuses to approve a manual entry of an employee outside the approver\'s structures', function (): void {
    $approver = grantStructures(scopeAttUser(['approve-attendance-manual']), [1]);
    scopeAttPersonnel('SA-MOUT', 2, 'Kənar');
    $entry = Schema::withoutForeignKeyConstraints(fn () => AttendanceManualEntry::query()->create([
        'tabel_no' => 'SA-MOUT',
        'date' => now()->toDateString(),
        'approval_status' => 'pending',
        'entered_by' => $approver->id,
        'reason' => 'Test',
    ]));
    $this->actingAs($approver);

    Livewire::test(ManualEntries::class)
        ->call('approve', $entry->id)
        ->assertForbidden();

    expect($entry->fresh()->approval_status)->toBe('pending');
});

it('lists only the overtime requests of employees inside the user\'s structures', function (): void {
    $user = grantStructures(scopeAttUser(['approve-attendance-overtime']), [1]);
    scopeAttPersonnel('SA-IN', 1, 'Daxili');
    scopeAttPersonnel('SA-OUT', 2, 'Kənar');
    scopeAttOvertime('SA-IN', $user);
    scopeAttOvertime('SA-OUT', $user);
    $this->actingAs($user);

    $tabelNos = Livewire::test(OvertimeBoard::class, scopeAttBoard())
        ->instance()->items()->getCollection()->pluck('tabel_no')->all();

    expect($tabelNos)->toBe(['SA-IN']);

    // Görünürlükdən kənar strukturu filtr kimi seçmək heç nə açmır.
    $filtered = Livewire::test(OvertimeBoard::class, scopeAttBoard())
        ->set('selectedStructureId', 2)
        ->instance()->items()->getCollection()->pluck('tabel_no')->all();

    expect($filtered)->toBe([]);
});

it('shows nothing to a user whose roles grant no structure', function (): void {
    $user = scopeAttUser(['approve-attendance-overtime']);
    scopeAttPersonnel('SA-IN', 1, 'Daxili');
    scopeAttOvertime('SA-IN', $user);
    $this->actingAs($user);

    expect(app(AttendanceStructureScopeReadService::class)->resolveIds(null))
        ->toBe([AttendanceStructureScopeReadService::NOTHING]);

    $tabelNos = Livewire::test(OvertimeBoard::class, scopeAttBoard())
        ->instance()->items()->getCollection()->pluck('tabel_no')->all();

    expect($tabelNos)->toBe([]);
});

it('resolves the attendance structure filter against the user\'s scope', function (): void {
    $scopeRead = app(AttendanceStructureScopeReadService::class);

    $this->actingAs(grantAllStructures(User::factory()->create()));
    expect($scopeRead->resolveIds(null))->toBe([])
        ->and($scopeRead->resolveIds(2))->toBe([2]);

    $this->actingAs(grantStructures(User::factory()->create(), [1]));
    expect($scopeRead->resolveIds(null))->toBe([1])
        ->and($scopeRead->resolveIds(1))->toBe([1])
        ->and($scopeRead->resolveIds(2))->toBe([AttendanceStructureScopeReadService::NOTHING]);
});
