<?php

use App\Models\AuditActivity;
use App\Models\CompensationRegime;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeLoan;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Audit\Application\Services\ActivityLogReader;
use App\Modules\Audit\Exports\ActivityLogExport;
use App\Modules\Audit\Livewire\ActivityLogDashboard;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * Audit jurnalı: maaş/kredit məbləğləri view-compensation-amounts olmadan maskalanır;
 * işçiyə bağlı subyektli sətirlər yalnız işçinin strukturu görünürlükdədirsə göstərilir.
 */

beforeEach(function (): void {
    foreach ([
        'countries' => [['id' => 1, 'code' => 'AZ']],
        'education_degrees' => [['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']],
        'structures' => [
            ['id' => 1, 'name' => 'İR', 'shortname' => 'İR', 'parent_id' => null, 'coefficient' => 1, 'code' => 10, 'level' => 1],
            ['id' => 2, 'name' => 'Maliyyə', 'shortname' => 'MAL', 'parent_id' => null, 'coefficient' => 1, 'code' => 20, 'level' => 1],
        ],
        'positions' => [['id' => 1, 'name' => 'Officer', 'approval_rank' => 10, 'is_approval_target' => false]],
        'work_norms' => [['id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Full']],
    ] as $table => $rows) {
        foreach ($rows as $row) {
            DB::table($table)->insertOrIgnore($row);
        }
    }

    $this->inside = scopeAuditPersonnel('AIN-001', 'Daxiliyev', 1);
    $this->outside = scopeAuditPersonnel('AOUT-001', 'Xariciyev', 2);
});

function scopeAuditPersonnel(string $tabelNo, string $surname, int $structureId): Personnel
{
    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => $tabelNo, 'surname' => $surname, 'name' => 'Test', 'patronymic' => 'Ata',
        'birthdate' => '1990-01-01', 'gender' => 1, 'mobile' => '994501112233', 'nationality_id' => 1,
        'pin' => 'A'.substr(md5($tabelNo), 0, 6), 'residental_address' => 'Bakı', 'education_degree_id' => 1,
        'structure_id' => $structureId, 'position_id' => 1, 'work_norm_id' => 1, 'join_work_date' => '2026-01-01',
        'added_by' => 1, 'is_pending' => false,
    ]));
}

/**
 * @param  list<string>  $permissions
 */
function scopeAuditViewer(array $permissions, ?array $structures): User
{
    $user = User::factory()->create();

    foreach (['show-audit-logs', ...$permissions] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    if ($structures === null) {
        grantAllStructures($user);
    } else {
        grantStructures($user, $structures);
    }

    return $user;
}

function scopeAuditCompensationLog(string $tabelNo, string $old, string $new): AuditActivity
{
    $compensation = EmployeeCompensation::withoutEvents(fn () => EmployeeCompensation::query()->create([
        'tabel_no' => $tabelNo,
        'regime_id' => CompensationRegime::query()->value('id') ?? DB::table('compensation_regimes')->insertGetId(['code' => 'test', 'name' => 'Test']),
        'base_amount' => $new,
        'effective_from' => '2026-01-01',
        'status' => 'active',
    ]));

    return AuditActivity::query()->create([
        'log_name' => 'employee_compensation',
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => EmployeeCompensation::class,
        'subject_id' => $compensation->id,
        'properties' => ['attributes' => ['base_amount' => $new, 'status' => 'active'], 'old' => ['base_amount' => $old, 'status' => 'draft']],
    ]);
}

it('masks compensation and loan amounts without view-compensation-amounts', function (): void {
    $activity = scopeAuditCompensationLog('AIN-001', '1111.11', '2222.22');
    $loan = EmployeeLoan::withoutEvents(fn () => EmployeeLoan::query()->create([
        'tabel_no' => 'AIN-001', 'type' => 'loan', 'principal' => 3333.33, 'monthly_installment' => 44.44,
        'remaining' => 3333.33, 'currency' => 'AZN', 'status' => 'active', 'start_on' => '2026-01-01',
    ]));
    $loanActivity = AuditActivity::query()->create([
        'log_name' => 'employee_loan', 'description' => 'created', 'event' => 'created',
        'subject_type' => EmployeeLoan::class, 'subject_id' => $loan->id,
        'properties' => ['attributes' => ['principal' => '3333.33', 'monthly_installment' => '44.44', 'remaining' => '3333.33']],
    ]);

    $viewer = scopeAuditViewer([], null);
    test()->actingAs($viewer);
    $reader = app(ActivityLogReader::class);

    $rows = collect($reader->changeRows($activity))->keyBy('key');
    expect($rows['base_amount']['new'])->toBe(ActivityLogReader::AMOUNT_MASK)
        ->and($rows['base_amount']['old'])->toBe(ActivityLogReader::AMOUNT_MASK)
        ->and($rows['status']['new'])->toBe('active');

    expect(collect($reader->changeRows($loanActivity))->pluck('new')->unique()->values()->all())->toBe([ActivityLogReader::AMOUNT_MASK]);

    $exported = (new ActivityLogExport)->map($activity);
    expect(end($exported))->not->toContain('2222.22')->not->toContain('1111.11');

    Livewire::actingAs($viewer)
        ->test(ActivityLogDashboard::class)
        ->call('selectActivity', $activity->id)
        ->assertDontSee('2222.22')
        ->assertDontSee('1111.11');

    $viewer->givePermissionTo(Permission::findOrCreate('view-compensation-amounts', 'web'));

    expect(collect($reader->changeRows($activity))->keyBy('key')['base_amount']['new'])->toBe('2222.22');
});

it('hides personnel-related rows outside the viewer\'s structures', function (): void {
    $inside = scopeAuditCompensationLog('AIN-001', '1', '2');
    $outside = scopeAuditCompensationLog('AOUT-001', '1', '2');
    $profileOutside = AuditActivity::query()->create([
        'log_name' => 'personnel_access', 'description' => 'Personnel profile opened', 'event' => 'profile_opened',
        'subject_type' => Personnel::class, 'subject_id' => $this->outside->id,
    ]);
    $login = AuditActivity::query()->create(['log_name' => 'default', 'description' => 'User logged in', 'event' => 'login']);

    $limited = scopeAuditViewer([], [1]);
    test()->actingAs($limited);

    $visible = app(ActivityLogReader::class)->query([])->pluck('id')->all();
    expect($visible)->toContain($inside->id)
        ->toContain($login->id)
        ->not->toContain($outside->id)
        ->not->toContain($profileOutside->id);

    Livewire::actingAs($limited)
        ->test(ActivityLogDashboard::class)
        ->call('selectActivity', $profileOutside->id)
        ->assertViewHas('selectedActivity', null);

    $nobody = scopeAuditViewer([], []);
    app()->forgetScopedInstances();
    test()->actingAs($nobody);
    expect(app(ActivityLogReader::class)->query([])->pluck('id')->all())->toBe([$login->id]);

    $all = scopeAuditViewer([], null);
    app()->forgetScopedInstances();
    test()->actingAs($all);
    expect(app(ActivityLogReader::class)->query([])->count())->toBe(4);
});
