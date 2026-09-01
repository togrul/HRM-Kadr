<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationRegime;
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
        $run = $runService->lock($run);
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
        $run = $runService->lock($runService->calculate($runService->createRun($period, $regimeId)));

        $loan->refresh();
        $this->assertSame('0.00', $loan->remaining);
        $this->assertSame('closed', $loan->status);
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
