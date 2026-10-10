<?php

use App\Models\EmployeeBankAccount;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Compensation\Livewire\Dashboard as CompensationDashboard;
use App\Modules\Compensation\Livewire\Tabs\BankTab;
use App\Modules\Compensation\Livewire\Tabs\HistoryTab;
use App\Modules\Payroll\Application\Services\PayrollExportService;
use App\Modules\Payroll\Livewire\Tabs\LoansTab;
use App\Modules\Payroll\Livewire\Tabs\PayslipsTab;
use App\Services\StructureService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/*
 * Kompensasiya və əmək haqqı ekranları işçidən törənən sətirləri (maaş, bank hesabı,
 * hesab vərəqəsi, kredit, ixrac) yalnız istifadəçinin struktur görünürlüyündə göstərir.
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

    $this->inside = scopeCompPayPersonnel('IN-001', 'Daxili', 1);
    $this->outside = scopeCompPayPersonnel('OUT-001', 'Xarici', 2);
});

function scopeCompPayPersonnel(string $tabelNo, string $surname, int $structureId): Personnel
{
    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => $tabelNo, 'surname' => $surname, 'name' => 'Test', 'patronymic' => 'Ata',
        'birthdate' => '1990-01-01', 'gender' => 1, 'mobile' => '994501112233', 'nationality_id' => 1,
        'pin' => 'P'.substr(md5($tabelNo), 0, 6), 'residental_address' => 'Bakı', 'education_degree_id' => 1,
        'structure_id' => $structureId, 'position_id' => 1, 'work_norm_id' => 1, 'join_work_date' => '2026-01-01',
        'added_by' => 1, 'is_pending' => false,
    ]));
}

/**
 * @param  list<string>  $permissions
 * @param  list<int>|null  $structures  null — heç bir struktur verilmir
 */
function scopeCompPayUser(array $permissions, ?array $structures = [1]): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    if ($structures !== null) {
        grantStructures($user, $structures);
    }

    test()->actingAs($user);

    return $user;
}

function scopeCompPayRun(): PayrollRun
{
    $period = PayrollPeriod::query()->create([
        'code' => '2026-06', 'year' => 2026, 'month' => 6,
        'starts_on' => '2026-06-01', 'ends_on' => '2026-06-30', 'currency' => 'AZN', 'status' => 'open',
    ]);

    $run = PayrollRun::query()->create(['payroll_period_id' => $period->id, 'status' => 'locked']);

    foreach (['IN-001' => 900, 'OUT-001' => 1900] as $tabelNo => $net) {
        Payslip::query()->create([
            'payroll_run_id' => $run->id, 'tabel_no' => $tabelNo,
            'gross' => $net + 100, 'total_deductions' => 100, 'net' => $net, 'employer_cost' => 0,
            'currency' => 'AZN', 'status' => 'locked',
        ]);
    }

    return $run;
}

it('limits the compensation employee search to the user\'s structures', function (): void {
    scopeCompPayUser(['show-compensation']);

    $results = Livewire::test(CompensationDashboard::class)
        ->set('personnelSearch', 'Test')
        ->instance()
        ->personnelResults();

    expect(collect($results)->pluck('tabel_no')->all())->toBe(['IN-001']);
});

it('refuses to pick an out-of-scope employee and keeps the pick locked', function (): void {
    scopeCompPayUser(['show-compensation']);

    Livewire::test(CompensationDashboard::class)
        ->call('selectPersonnel', 'OUT-001', 'Xarici')
        ->assertForbidden();

    expect(fn () => Livewire::test(CompensationDashboard::class)->set('selectedTabelNo', 'OUT-001'))
        ->toThrow(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
});

it('shows a user without any structure no employee at all (fail closed)', function (): void {
    scopeCompPayUser(['show-compensation'], null);

    $results = Livewire::test(CompensationDashboard::class)
        ->set('personnelSearch', 'Test')
        ->instance()
        ->personnelResults();

    expect($results)->toBe([]);
});

it('does not read or delete an out-of-scope employee\'s bank account', function (): void {
    scopeCompPayUser(['show-compensation', 'manage-compensation']);
    $account = EmployeeBankAccount::query()->create([
        'tabel_no' => 'OUT-001', 'iban' => 'AZ00TEST0000000000000000001', 'is_primary' => true, 'is_active' => true,
    ]);

    $tab = Livewire::test(BankTab::class, ['tabelNo' => 'OUT-001']);
    expect($tab->instance()->bankAccounts()->all())->toBe([]);

    $tab->call('deleteBank', $account->id)->assertForbidden();
    expect(EmployeeBankAccount::query()->whereKey($account->id)->exists())->toBeTrue();

    expect(Livewire::test(HistoryTab::class, ['tabelNo' => 'OUT-001'])->instance()->history()->all())->toBe([]);
});

it('lists only in-scope payslips and refuses opening an out-of-scope one', function (): void {
    scopeCompPayUser(['show-payroll', 'view-compensation-amounts']);
    $run = scopeCompPayRun();
    $outsideId = Payslip::query()->where('tabel_no', 'OUT-001')->value('id');

    $tab = Livewire::test(PayslipsTab::class, ['runId' => $run->id]);
    expect($tab->instance()->runPayslips()->pluck('tabel_no')->all())->toBe(['IN-001']);

    $tab->call('viewPayslip', $outsideId)->assertForbidden();
});

it('filters payroll exports to the user\'s structures', function (): void {
    $user = scopeCompPayUser(['show-payroll', 'export-payroll', 'view-compensation-amounts']);
    $run = scopeCompPayRun();
    $scope = app(StructureService::class)->scopeFor($user);

    $service = app(PayrollExportService::class);
    expect(collect($service->bankRows($run, $scope))->pluck('tabel_no')->all())->toBe(['IN-001'])
        ->and(collect($service->stateRows($run, $scope))->pluck('tabel_no')->all())->toBe(['IN-001'])
        ->and(collect($service->bankRows($run))->pluck('tabel_no')->sort()->values()->all())->toBe(['IN-001', 'OUT-001']);
});

it('blocks the admin payslip print for an out-of-scope employee', function (): void {
    scopeCompPayUser(['show-payroll', 'view-compensation-amounts']);
    $run = scopeCompPayRun();

    $this->get(route('payroll.payslip.print', Payslip::query()->where('tabel_no', 'OUT-001')->value('id')))->assertForbidden();
    $this->get(route('payroll.payslip.print', Payslip::query()->where('tabel_no', 'IN-001')->value('id')))->assertOk();
});

it('does not delete a loan of an employee other than the picked one', function (): void {
    scopeCompPayUser(['show-payroll', 'manage-payroll']);
    $loan = EmployeeLoan::query()->create([
        'tabel_no' => 'OUT-001', 'type' => 'loan', 'principal' => 500, 'monthly_installment' => 50,
        'remaining' => 500, 'currency' => 'AZN', 'status' => 'active', 'start_on' => '2026-01-01',
    ]);

    expect(fn () => Livewire::test(LoansTab::class, ['tabelNo' => 'IN-001', 'label' => 'Daxili'])->call('deleteLoan', $loan->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test(LoansTab::class, ['tabelNo' => 'OUT-001', 'label' => 'Xarici'])->assertForbidden();

    expect(EmployeeLoan::query()->whereKey($loan->id)->exists())->toBeTrue();
});
