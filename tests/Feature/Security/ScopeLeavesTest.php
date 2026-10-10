<?php

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\LeaveSickCertificate;
use App\Models\LeaveType;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Leaves\Application\Services\SickCertificateRegister;
use App\Modules\Leaves\Exports\LeaveExport;
use App\Modules\Leaves\Livewire\Leaves;
use App\Modules\Leaves\Livewire\SickCertificates\SickCertificates;
use App\Notifications\NewLeaveRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

/**
 * İcazələr modulu struktur görünürlüyünə tabedir (fail closed): siyahı, ixrac, xəstəlik
 * vərəqələri reyestri, qeyd səviyyəli policy və yeni icazə bildirişlərinin auditoriyası.
 */
beforeEach(function (): void {
    foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
        OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
    }

    DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
    DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
    DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
    Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
    Structure::query()->firstOrCreate(['id' => 2], ['name' => 'Maliyyə', 'shortname' => 'MAL', 'code' => 2, 'level' => 1]);
    Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);

    scopeLeavePerson('SL-IN', 'Görünənov', 1);
    scopeLeavePerson('SL-OUT', 'Gizlinov', 2);

    $this->type = LeaveType::query()->create(['name' => 'Ailə icazəsi', 'max_days' => 0, 'requires_document' => false]);
    $this->inLeave = scopeLeave('SL-IN', $this->type->id);
    $this->outLeave = scopeLeave('SL-OUT', $this->type->id);
});

function scopeLeavePerson(string $tabelNo, string $surname, int $structureId): Personnel
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
        'structure_id' => $structureId,
        'position_id' => 1,
        'work_norm_id' => 1,
        'join_work_date' => '2021-01-01',
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

function scopeLeave(string $tabelNo, int $typeId): Leave
{
    return Leave::withoutEvents(fn (): Leave => Leave::query()->create([
        'tabel_no' => $tabelNo,
        'leave_type_id' => $typeId,
        'starts_at' => '2026-11-02',
        'ends_at' => '2026-11-03',
        'duration_unit' => 'day',
        'reason' => 'Səbəb '.$tabelNo,
        'status_id' => OrderStatusEnum::PENDING->value,
    ]));
}

/**
 * @param  list<string>  $permissions
 * @param  list<int>|null  $structures  null — heç bir struktur verilmir
 */
function scopeLeaveUser(array $permissions, ?array $structures): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    if ($structures !== null) {
        grantStructures($user, $structures);
    }

    return $user;
}

it('lists and exports only the leaves of employees inside the user\'s structures', function (): void {
    $this->actingAs(scopeLeaveUser(['show-leaves', 'export-leaves'], [1]));
    Excel::fake();

    $component = Livewire::test(Leaves::class)
        ->assertSee('Görünənov')
        ->assertDontSee('Gizlinov');

    expect($component->instance()->statusCounts()['all'])->toBe(1);

    $component->call('exportExcel');

    Excel::assertDownloaded('leaves-'.now()->format('d.m.Y H:i').'.xlsx', function (LeaveExport $export): bool {
        $tabelNos = collect($export->rows)->pluck('tabel_no')->all();

        return $tabelNos === ['SL-IN'];
    });
});

it('shows nothing to a user whose roles grant no structure (fail closed)', function (): void {
    $this->actingAs(scopeLeaveUser(['show-leaves'], null));

    $component = Livewire::test(Leaves::class)
        ->assertDontSee('Görünənov')
        ->assertDontSee('Gizlinov');

    expect($component->instance()->statusCounts()['all'])->toBe(0);
});

it('shows every leave to an all-structures role', function (): void {
    $this->actingAs(grantAllStructures(scopeLeaveUser(['show-leaves'], null)));

    Livewire::test(Leaves::class)
        ->assertSee('Görünənov')
        ->assertSee('Gizlinov');
});

it('requires the leave\'s structure in scope for record-level policy checks', function (): void {
    $user = scopeLeaveUser(['show-leaves', 'edit-leaves', 'delete-leaves'], [1]);

    foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
        expect($user->can($ability, $this->inLeave))->toBeTrue("{$ability} in scope")
            ->and($user->can($ability, $this->outLeave))->toBeFalse("{$ability} out of scope");
    }

    // Sinif səviyyəli yoxlama yalnız icazəyə baxır.
    expect($user->can('viewAny', Leave::class))->toBeTrue()
        ->and($user->can('update', Leave::class))->toBeTrue();

    $unscoped = scopeLeaveUser(['show-leaves', 'edit-leaves'], null);
    expect($unscoped->can('view', $this->inLeave))->toBeFalse()
        ->and($unscoped->can('update', $this->inLeave))->toBeFalse();
});

it('narrows the sick-certificate register, its export and policy to the user\'s structures', function (): void {
    $user = scopeLeaveUser(['show-leaves', 'edit-leaves', 'export-leaves'], [1]);
    $this->actingAs($user);

    $inCertificate = LeaveSickCertificate::query()->forceCreate(['leave_id' => $this->inLeave->id, 'series' => 'AB', 'number' => '700001']);
    $outCertificate = LeaveSickCertificate::query()->forceCreate(['leave_id' => $this->outLeave->id, 'series' => 'AB', 'number' => '700002']);

    expect(app(SickCertificateRegister::class)->listing()->pluck('leave_sick_certificates.id')->all())
        ->toBe([$inCertificate->id])
        ->and(app(SickCertificateRegister::class)->stats()['total'])->toBe(1);

    Livewire::test(SickCertificates::class)
        ->assertSee('700001')
        ->assertDontSee('700002');

    expect($user->can('view', $inCertificate))->toBeTrue()
        ->and($user->can('view', $outCertificate))->toBeFalse()
        ->and($user->can('update', $outCertificate))->toBeFalse();
});

it('notifies only get-notification holders whose scope covers the employee', function (): void {
    Notification::fake();

    $inScope = scopeLeaveUser(['get-notification'], [1]);
    $outOfScope = scopeLeaveUser(['get-notification'], [2]);
    $noScope = scopeLeaveUser(['get-notification'], null);
    $everything = grantAllStructures(scopeLeaveUser(['get-notification'], null));

    Leave::query()->create([
        'tabel_no' => 'SL-IN',
        'leave_type_id' => $this->type->id,
        'starts_at' => '2026-12-01',
        'ends_at' => '2026-12-01',
        'duration_unit' => 'day',
        'reason' => 'Yeni',
        'status_id' => OrderStatusEnum::PENDING->value,
    ]);

    Notification::assertSentTo([$inScope, $everything], NewLeaveRequested::class);
    Notification::assertNotSentTo([$outOfScope, $noScope], NewLeaveRequested::class);
});
