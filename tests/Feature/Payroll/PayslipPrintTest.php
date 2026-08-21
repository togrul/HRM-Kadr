<?php

namespace Tests\Feature\Payroll;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayslipPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_owner_can_open_their_locked_payslip_print(): void
    {
        $personnel = $this->makePersonnel('owner@example.test');
        $payslip = $this->makeLockedPayslip($personnel->tabel_no);

        $user = User::factory()->create(['email' => 'owner@example.test']);
        $this->actingAs($user);

        $this->get(route('payroll.payslip.print', $payslip->id))
            ->assertOk()
            ->assertSee($personnel->surname);
    }

    public function test_other_employee_cannot_open_someone_elses_payslip(): void
    {
        $personnel = $this->makePersonnel('a@example.test');
        $payslip = $this->makeLockedPayslip($personnel->tabel_no);

        $intruder = User::factory()->create(['email' => 'intruder@example.test']);
        $this->actingAs($intruder);

        $this->get(route('payroll.payslip.print', $payslip->id))->assertForbidden();
    }

    public function test_unlocked_payslip_is_not_printable(): void
    {
        $personnel = $this->makePersonnel('c@example.test');
        $payslip = $this->makeLockedPayslip($personnel->tabel_no, 'calculated');

        $user = User::factory()->create(['email' => 'c@example.test']);
        $this->actingAs($user);

        $this->get(route('payroll.payslip.print', $payslip->id))->assertNotFound();
    }

    private function makeLockedPayslip(string $tabelNo, string $status = 'locked'): Payslip
    {
        $period = PayrollPeriod::create([
            'code' => '2026-06', 'year' => 2026, 'month' => 6,
            'starts_on' => '2026-06-01', 'ends_on' => '2026-06-30', 'currency' => 'AZN', 'status' => 'open',
        ]);
        $run = PayrollRun::create(['payroll_period_id' => $period->id, 'status' => $status]);

        return Payslip::create([
            'payroll_run_id' => $run->id, 'tabel_no' => $tabelNo,
            'gross' => 1000, 'total_deductions' => 100, 'net' => 900, 'employer_cost' => 0,
            'currency' => 'AZN', 'status' => $status,
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
