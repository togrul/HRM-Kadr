<?php

namespace Tests\Feature\Personnel;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\MyHr\MyHrPayslips;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrPayslipsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_employee_sees_only_their_own_locked_payslips(): void
    {
        $self = $this->makePersonnel('self@example.test');
        $other = $this->makePersonnel('other@example.test');

        $period = PayrollPeriod::create([
            'code' => '2026-06', 'year' => 2026, 'month' => 6,
            'starts_on' => '2026-06-01', 'ends_on' => '2026-06-30', 'currency' => 'AZN', 'status' => 'open',
        ]);
        $lockedRun = PayrollRun::create(['payroll_period_id' => $period->id, 'status' => 'locked']);
        $draftRun = PayrollRun::create(['payroll_period_id' => $period->id, 'status' => 'calculated']);

        $mine = $this->makePayslip($lockedRun->id, $self->tabel_no, 'locked', 1000, 900);
        $this->makePayslip($lockedRun->id, $other->tabel_no, 'locked', 2000, 1800);   // someone else
        $this->makePayslip($draftRun->id, $self->tabel_no, 'calculated', 1000, 900);    // not finalised

        $user = User::factory()->create(['email' => 'self@example.test']);
        $user->givePermissionTo(Permission::findOrCreate('show-my-hr', 'web'));
        $this->actingAs($user);

        $instance = Livewire::test(MyHrPayslips::class, ['personnelId' => $self->id])->instance();

        $this->assertCount(1, $instance->payslips);
        $this->assertSame($mine->id, $instance->payslips->first()->id);
    }

    public function test_employee_cannot_open_another_employees_payslip(): void
    {
        $self = $this->makePersonnel('a@example.test');
        $other = $this->makePersonnel('b@example.test');

        $period = PayrollPeriod::create([
            'code' => '2026-05', 'year' => 2026, 'month' => 5,
            'starts_on' => '2026-05-01', 'ends_on' => '2026-05-31', 'currency' => 'AZN', 'status' => 'open',
        ]);
        $run = PayrollRun::create(['payroll_period_id' => $period->id, 'status' => 'locked']);
        $othersPayslip = $this->makePayslip($run->id, $other->tabel_no, 'locked', 2000, 1800);

        $user = User::factory()->create(['email' => 'a@example.test']);
        $user->givePermissionTo(Permission::findOrCreate('show-my-hr', 'web'));
        $this->actingAs($user);

        $instance = Livewire::test(MyHrPayslips::class, ['personnelId' => $self->id])
            ->call('viewPayslip', $othersPayslip->id)
            ->instance();

        $this->assertNull($instance->selectedPayslip);
    }

    private function makePayslip(int $runId, string $tabelNo, string $status, float $gross, float $net): Payslip
    {
        return Payslip::create([
            'payroll_run_id' => $runId,
            'tabel_no' => $tabelNo,
            'gross' => $gross,
            'total_deductions' => $gross - $net,
            'net' => $net,
            'employer_cost' => 0,
            'currency' => 'AZN',
            'status' => $status,
        ]);
    }

    private function makePersonnel(string $email): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => $email,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }

    private function seedReferenceData(): void
    {
        if (! DB::table('countries')->where('id', 1)->exists()) {
            DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        }
        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        }
        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert(['id' => 1, 'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1]);
        }
        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert(['id' => 1, 'name' => 'Officer', 'approval_rank' => 10, 'is_approval_target' => false]);
        }
        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        }
    }
}
