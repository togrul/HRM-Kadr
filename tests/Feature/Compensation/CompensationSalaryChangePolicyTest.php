<?php

use App\Models\CompensationRegime;
use App\Models\EmployeeCompensation;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Compensation\Livewire\Tabs\AssignmentsTab;
use App\Modules\Personnel\Contracts\GuardsPersonnelChanges;
use App\Modules\Personnel\Contracts\ManagesPersonnelChangePolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

/*
 * Əmək haqqı dəyişiklik siyasətinin «salary» qrupudur: Kompensasiya ekranından əl ilə
 * yenidən təyinat `order` rejimində bağlıdır, `journal` rejimində səbəb tələb edir; əmək
 * haqqının dəyişdirilməsi əmri və ilk təyinat isə siyasətdən asılı deyil.
 */

function salaryPolicyManager(): User
{
    $user = User::factory()->create();
    foreach (['show-compensation', 'manage-compensation', 'view-compensation-amounts'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    test()->actingAs($user);

    return $user;
}

function salaryPolicyEmployee(): Personnel
{
    foreach ([
        'countries' => ['id' => 1, 'code' => 'AZ'],
        'education_degrees' => ['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor'],
        'structures' => ['id' => 1, 'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1],
        'positions' => ['id' => 1, 'name' => 'Officer', 'approval_rank' => 10, 'is_approval_target' => false],
        'work_norms' => ['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time'],
    ] as $table => $row) {
        if (! DB::table($table)->where('id', 1)->exists()) {
            DB::table($table)->insert($row);
        }
    }

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'SAL-001', 'surname' => 'Doe', 'name' => 'Jane', 'patronymic' => 'Smith',
        'birthdate' => '1990-01-01', 'gender' => 1, 'mobile' => '994501112233', 'nationality_id' => 1,
        'pin' => 'SAL0001', 'residental_address' => 'Main st', 'education_degree_id' => 1,
        'structure_id' => 1, 'position_id' => 1, 'work_norm_id' => 1, 'join_work_date' => '2026-01-01',
        'added_by' => 1, 'is_pending' => false,
    ]));
}

function salaryPolicyAssign(string $tabelNo, string $amount, string $from): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test(AssignmentsTab::class, ['tabelNo' => $tabelNo])
        ->set('assignmentForm.regime_id', CompensationRegime::query()->where('code', 'private')->value('id'))
        ->set('assignmentForm.base_amount', $amount)
        ->set('assignmentForm.currency', 'AZN')
        ->set('assignmentForm.effective_from', $from);
}

function salaryPolicyActive(string $tabelNo): ?string
{
    return EmployeeCompensation::query()->where('tabel_no', $tabelNo)->where('status', 'active')->value('base_amount');
}

it('lets the first assignment through and then refuses a manual change in order mode', function (): void {
    salaryPolicyManager();
    $employee = salaryPolicyEmployee();

    salaryPolicyAssign($employee->tabel_no, '1000', '2026-01-01')
        ->call('saveAssignment')
        ->assertHasNoErrors();

    $component = salaryPolicyAssign($employee->tabel_no, '1500', '2026-06-01')
        ->assertSee(__('compensation::dashboard.assignments.order_only'))
        ->call('saveAssignment')
        ->assertHasErrors(['changeReason']);

    expect($component->errors()->first('changeReason'))->toContain(__('personnel::change_policy.groups.salary.label'))
        ->and(salaryPolicyActive($employee->tabel_no))->toBe('1000.00')
        ->and(EmployeeCompensation::query()->where('tabel_no', $employee->tabel_no)->count())->toBe(1);
});

it('requires a reason in journal mode and audits the old and new salary', function (): void {
    $user = salaryPolicyManager();
    $employee = salaryPolicyEmployee();
    salaryPolicyAssign($employee->tabel_no, '1000', '2026-01-01')->call('saveAssignment')->assertHasNoErrors();
    app(ManagesPersonnelChangePolicy::class)->setMode('salary', 'journal');

    salaryPolicyAssign($employee->tabel_no, '1200', '2026-06-01')
        ->assertSee(__('compensation::dashboard.assignments.reason'))
        ->call('saveAssignment')
        ->assertHasErrors(['changeReason'])
        ->set('changeReason', 'İllik indeksləşdirmə')
        ->call('saveAssignment')
        ->assertHasNoErrors()
        ->assertSet('changeReason', '');

    $entry = Activity::query()->where('event', 'change_policy_journal')->sole();
    expect(salaryPolicyActive($employee->tabel_no))->toBe('1200.00')
        ->and($entry->properties['field_group'])->toBe('salary')
        ->and($entry->properties['reason'])->toBe('İllik indeksləşdirmə')
        ->and($entry->properties['old']['base_amount'])->toBe('1000.00')
        ->and($entry->properties['attributes']['base_amount'])->toBe('1200')
        ->and($entry->properties['context']['tabel_no'])->toBe($employee->tabel_no)
        ->and((int) $entry->causer_id)->toBe($user->id);
});

it('allows a manual change in free mode without a reason', function (): void {
    salaryPolicyManager();
    $employee = salaryPolicyEmployee();
    salaryPolicyAssign($employee->tabel_no, '1000', '2026-01-01')->call('saveAssignment')->assertHasNoErrors();
    app(ManagesPersonnelChangePolicy::class)->setMode('salary', 'free');

    salaryPolicyAssign($employee->tabel_no, '1100', '2026-06-01')->call('saveAssignment')->assertHasNoErrors();

    expect(salaryPolicyActive($employee->tabel_no))->toBe('1100.00')
        ->and(Activity::query()->where('event', 'change_policy_journal')->count())->toBe(0);
});

it('still applies a salary change order while salary is order-only', function (): void {
    salaryPolicyManager();
    $employee = salaryPolicyEmployee();
    salaryPolicyAssign($employee->tabel_no, '1000', '2026-01-01')->call('saveAssignment')->assertHasNoErrors();

    $state = app(GuardsPersonnelChanges::class)->allowForEffect('salary_change', fn () => app(CompensationService::class)
        ->changeSalaryFromOrder($employee->tabel_no, 1300, Carbon::parse('2026-07-01'), '12-K'));

    expect($state)->not->toBeNull()
        ->and(salaryPolicyActive($employee->tabel_no))->toBe('1300.00');

    // Əmr kontekstindən kənar əl ilə yenidən təyinat isə rədd olunur.
    expect(fn () => app(CompensationService::class)->assignManually($employee->tabel_no, [
        'regime_id' => CompensationRegime::query()->where('code', 'private')->value('id'),
        'base_amount' => 2000, 'currency' => 'AZN', 'effective_from' => '2026-08-01',
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});
