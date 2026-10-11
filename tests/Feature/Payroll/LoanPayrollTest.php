<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationRegime;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeLoan;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\LoanService;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoanPayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_loan_installment_is_deducted_and_repaid_on_lock_then_restored_on_reopen(): void
    {
        $personnel = $this->makePersonnel('loan@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        app(CompensationService::class)->assignCompensation(
            $personnel->tabel_no,
            ['regime_id' => $regimeId, 'base_amount' => 3000, 'effective_from' => '2026-01-01'],
        );
        $loan = app(LoanService::class)->createLoan($personnel->tabel_no, [
            'type' => 'loan', 'principal' => 500, 'monthly_installment' => 200, 'start_on' => '2026-01-01',
        ]);

        // Payslip carries a 200 loan deduction line.
        $calc = app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-06-30', 2026, 6);
        $loanLine = collect($calc['lines'])->firstWhere('code', 'loan');
        $this->assertNotNull($loanLine);
        $this->assertSame(200.0, (float) $loanLine['amount']);

        $period = app(PayrollPeriodService::class)->createPeriod(2026, 6);
        $runService = app(PayrollRunService::class);
        $run = $runService->calculate($runService->createRun($period, $regimeId));

        // Lock applies the repayment: remaining 500 → 300, one ledger row.
        $run = $runService->lock($runService->approve($run));
        $this->assertSame('300.00', $loan->fresh()->remaining);
        $this->assertSame(1, $loan->repayments()->count());

        // Reopen reverses it.
        $runService->reopen($run);
        $this->assertSame('500.00', $loan->fresh()->remaining);
        $this->assertSame(0, $loan->repayments()->count());
    }

    public function test_loan_closes_when_remaining_reaches_zero(): void
    {
        $personnel = $this->makePersonnel('close@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        app(CompensationService::class)->assignCompensation(
            $personnel->tabel_no,
            ['regime_id' => $regimeId, 'base_amount' => 3000, 'effective_from' => '2026-01-01'],
        );
        $loan = EmployeeLoan::create([
            'tabel_no' => $personnel->tabel_no, 'type' => 'loan',
            'principal' => 500, 'monthly_installment' => 200, 'remaining' => 150,
            'currency' => 'AZN', 'status' => 'active', 'start_on' => '2026-01-01',
        ]);

        $period = app(PayrollPeriodService::class)->createPeriod(2026, 7);
        $runService = app(PayrollRunService::class);
        $run = $runService->lock($runService->approve($runService->calculate($runService->createRun($period, $regimeId))));

        $loan->refresh();
        $this->assertSame('0.00', $loan->remaining);
        $this->assertSame('closed', $loan->status);
    }

    public function test_an_off_cycle_run_takes_no_instalment_and_repays_nothing(): void
    {
        [$personnel, $regimeId] = $this->employeeWithPay('offcycle@example.test', 3000);
        $loan = app(LoanService::class)->createLoan($personnel->tabel_no, ['principal' => 1000, 'monthly_installment' => 200, 'start_on' => '2026-01-01']);

        $runs = app(PayrollRunService::class);
        $period = app(PayrollPeriodService::class)->createPeriod(2026, 6);
        $offCycle = $runs->lock($runs->approve($runs->calculate($runs->createRun($period, $regimeId, null, 'off_cycle'))));

        $this->assertNull($offCycle->payslips()->first()?->lines()->where('code', 'loan')->first());
        $this->assertSame('1000.00', $loan->fresh()->remaining);
        $this->assertSame(0, $loan->repayments()->count());
    }

    public function test_final_settlement_takes_the_remaining_debt_only_up_to_the_legal_cap(): void
    {
        [$personnel, $regimeId] = $this->employeeWithPay('leaver@example.test', 3000);
        $loan = app(LoanService::class)->createLoan($personnel->tabel_no, ['principal' => 5000, 'monthly_installment' => 200, 'start_on' => '2026-01-01']);
        $this->leaves($personnel, '2026-06-15');

        $runs = app(PayrollRunService::class);
        $period = app(PayrollPeriodService::class)->createPeriod(2026, 6);

        // Əmək haqqı müqaviləsi bitib: adi run onu artıq ödəmir, son haqq-hesab ödəyir.
        $regular = $runs->calculate($runs->createRun($period, $regimeId));
        $this->assertSame(0, $regular->payslips()->where('tabel_no', $personnel->tabel_no)->count());

        $settlement = $runs->calculate($runs->createRun($period, $regimeId, null, 'final_settlement'));
        $payslip = $settlement->payslips()->where('tabel_no', $personnel->tabel_no)->firstOrFail();
        $loanLine = (float) $payslip->lines()->where('code', 'loan')->value('amount');
        $wageDue = (float) $payslip->gross - (float) $payslip->lines()->where('kind', 'deduction')->where('is_statutory', true)->sum('amount');

        // Taksit (200) yox, qalan borc — amma ödənişin 20 %-i qədər (ƏM m.176.1).
        $this->assertGreaterThan(200.0, $loanLine);
        $this->assertLessThanOrEqual(round($wageDue * 0.20, 2), $loanLine);
        $this->assertEqualsWithDelta($wageDue * 0.20, $loanLine, 0.011);

        $runs->lock($runs->approve($settlement));
        $loan->refresh();
        // Sığmayan hissə borc olaraq qalır.
        $this->assertSame(number_format(5000 - $loanLine, 2, '.', ''), $loan->remaining);
        $this->assertSame('active', $loan->status);
        $this->assertSame(1, $loan->repayments()->count());

        // Yenidən açılış borcu tam bərpa edir.
        $runs->reopen($settlement->fresh());
        $this->assertSame('5000.00', $loan->fresh()->remaining);
    }

    public function test_final_settlement_closes_a_debt_that_fits_and_counts_other_deductions_against_the_cap(): void
    {
        [$personnel] = $this->employeeWithPay('fits@example.test', 3000);
        $first = EmployeeLoan::create([
            'tabel_no' => $personnel->tabel_no, 'type' => 'advance', 'principal' => 300, 'monthly_installment' => 100,
            'remaining' => 120, 'currency' => 'AZN', 'status' => 'active', 'start_on' => '2026-01-01',
        ]);
        $second = EmployeeLoan::create([
            'tabel_no' => $personnel->tabel_no, 'type' => 'loan', 'principal' => 500, 'monthly_installment' => 50,
            'remaining' => 180, 'currency' => 'AZN', 'status' => 'active', 'start_on' => '2026-02-01',
        ]);
        $loans = app(LoanService::class);

        // Borc (300) həddə (2000 × 20 % = 400) sığır → tam tutulur.
        $this->assertSame(300.0, $loans->settlementAmount($personnel->tabel_no, 2000.0));
        // Başqa tutulmalar (350) həddin çoxunu tutub → yalnız 50 qalır.
        $this->assertSame(50.0, $loans->settlementAmount($personnel->tabel_no, 2000.0, 350.0));
        // Hədd artıq dolubsa heç nə tutulmur.
        $this->assertSame(0.0, $loans->settlementAmount($personnel->tabel_no, 2000.0, 500.0));

        $this->leaves($personnel, '2026-07-20');
        $runs = app(PayrollRunService::class);
        $period = app(PayrollPeriodService::class)->createPeriod(2026, 7);
        $settlement = $runs->calculate($runs->createRun($period, null, null, 'final_settlement'));
        $this->assertSame(300.0, (float) $settlement->payslips()->firstOrFail()->lines()->where('code', 'loan')->value('amount'));

        $runs->lock($runs->approve($settlement));
        $this->assertSame(['0.00', 'closed'], [$first->fresh()->remaining, $first->fresh()->status]);
        $this->assertSame(['0.00', 'closed'], [$second->fresh()->remaining, $second->fresh()->status]);
    }

    public function test_a_leaver_still_on_the_regular_run_is_settled_there_and_not_paid_twice(): void
    {
        [$personnel, $regimeId] = $this->employeeWithPay('regular-leaver@example.test', 3000);
        $loan = app(LoanService::class)->createLoan($personnel->tabel_no, ['principal' => 5000, 'monthly_installment' => 200, 'start_on' => '2026-01-01']);
        // Əmək haqqı müqaviləsi ayın sonuna qədər açıqdır: son maaşı adi run ödəyir.
        $this->leaves($personnel, '2026-08-31', endPay: false);

        $runs = app(PayrollRunService::class);
        $period = app(PayrollPeriodService::class)->createPeriod(2026, 8);
        $regular = $runs->calculate($runs->createRun($period, $regimeId));
        $this->assertGreaterThan(200.0, (float) $regular->payslips()->firstOrFail()->lines()->where('code', 'loan')->value('amount'));

        $settlement = $runs->calculate($runs->createRun($period, $regimeId, null, 'final_settlement'));
        $this->assertSame(0, $settlement->payslips()->count());

        $runs->lock($runs->approve($regular));
        $this->assertSame(1, $loan->repayments()->count());
    }

    /**
     * @return array{0: Personnel, 1: int}
     */
    private function employeeWithPay(string $email, float $base): array
    {
        $personnel = $this->makePersonnel($email);
        $regimeId = (int) CompensationRegime::where('code', 'private')->value('id');
        app(CompensationService::class)->assignCompensation(
            $personnel->tabel_no,
            ['regime_id' => $regimeId, 'base_amount' => $base, 'effective_from' => '2026-01-01'],
        );

        return [$personnel, $regimeId];
    }

    private function leaves(Personnel $personnel, string $lastDay, bool $endPay = true): void
    {
        $personnel->forceFill(['leave_work_date' => $lastDay])->saveQuietly();
        if ($endPay) {
            EmployeeCompensation::query()->where('tabel_no', $personnel->tabel_no)->update(['effective_to' => $lastDay]);
        }
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
