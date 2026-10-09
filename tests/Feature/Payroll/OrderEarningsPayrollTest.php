<?php

namespace Tests\Feature\Payroll;

use App\Models\ApiToken;
use App\Models\AttendanceOvertimeRequest;
use App\Models\CompensationRegime;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Personnel;
use App\Models\RetroPayment;
use App\Models\User;
use App\Modules\Attendance\Contracts\OrderRestDayWork;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Compensation\Contracts\OrderCompensationSync;
use App\Modules\Integration\Support\ConfiguredPayrollOwnership;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

/**
 * What approved orders entitle an employee to reaches the payslip: rest-day / holiday
 * work paid at double the hourly rate (or a day off instead, unpaid), and the
 * substitution extra pay prorated to the month — both through the same statutory path,
 * recalculated with the run, gone once the order is revoked. When finance owns payroll
 * the same facts travel in the integration feeds instead.
 *
 * November 2026 has 21 working days (no calendar exceptions in the test database), so
 * the norm is 21 × 8 h = 168 h; a 1 680 AZN salary gives a 10 AZN hourly rate.
 */
class OrderEarningsPayrollTest extends TestCase
{
    use RefreshDatabase;

    private int $regimeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
        $this->regimeId = (int) CompensationRegime::where('code', 'private')->value('id');
        $this->actingAs(User::factory()->create());
    }

    public function test_double_pay_rest_day_work_is_paid_at_twice_the_hourly_rate_and_taxed(): void
    {
        $personnel = $this->employee(1680);
        $before = app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-11-30', 2026, 11);

        $this->restDayWork($personnel, '2026-11-08', OrderRestDayWork::COMPENSATION_DOUBLE_PAY);

        $payslip = $this->calculateNovember($personnel);
        $line = $payslip->lines->firstWhere('code', 'rest_day_work');

        // 1 680 ÷ 168 h = 10 AZN/h; 8 h × 10 × 2 = 160.
        $this->assertNotNull($line);
        $this->assertSame(160.0, (float) $line->amount);
        $this->assertTrue((bool) $line->taxable);
        $this->assertStringContainsString('8', (string) $line->name);
        $this->assertSame(1840.0, (float) $payslip->gross);
        $this->assertGreaterThan((float) $before['total_deductions'], (float) $payslip->total_deductions, 'The extra pay goes through the statutory deductions.');
    }

    public function test_a_day_off_compensation_adds_no_pay_but_stays_on_record(): void
    {
        $personnel = $this->employee(1680);

        $this->restDayWork($personnel, '2026-11-08', OrderRestDayWork::COMPENSATION_DAY_OFF);

        $payslip = $this->calculateNovember($personnel);

        $this->assertNull($payslip->lines->firstWhere('code', 'rest_day_work'));
        $this->assertSame(1680.0, (float) $payslip->gross);
        $this->assertSame(OrderRestDayWork::COMPENSATION_DAY_OFF, AttendanceOvertimeRequest::query()->sole()->compensation);
    }

    public function test_the_same_day_is_paid_once_and_ordinary_overtime_is_not_paid_as_rest_day_work(): void
    {
        $personnel = $this->employee(1680);

        $this->restDayWork($personnel, '2026-11-08', OrderRestDayWork::COMPENSATION_DOUBLE_PAY);
        $this->restDayWork($personnel, '2026-11-08', OrderRestDayWork::COMPENSATION_DOUBLE_PAY);
        AttendanceOvertimeRequest::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-11-10',
            'requested_minutes' => 120,
            'approved_minutes' => 120,
            'status' => 'approved',
            'source' => 'manual',
            'requested_by' => auth()->id(),
        ]);

        $lines = $this->calculateNovember($personnel)->lines->where('code', 'rest_day_work');

        $this->assertCount(1, $lines);
        $this->assertSame(160.0, (float) $lines->first()->amount);
    }

    public function test_substitution_extra_pay_is_a_percent_of_the_own_salary_prorated_by_days(): void
    {
        $personnel = $this->employee(3000);

        $this->substitution($personnel, ['start_date' => '2026-11-02', 'end_date' => '2026-11-20', 'extra_pay_percent' => 30.0, 'substituted_name' => 'Həsənova Leyla Əli']);

        $payslip = $this->calculateNovember($personnel);
        $line = $payslip->lines->firstWhere('code', 'substitution');

        // 3 000 × 30 % = 900 a month; 19 of November's 30 days → 570.
        $this->assertSame(570.0, (float) $line?->amount);
        $this->assertStringContainsString('Həsənova Leyla Əli', (string) $line->name);
        $this->assertSame(3570.0, (float) $payslip->gross);
    }

    public function test_a_fixed_amount_substitution_is_a_monthly_amount_prorated_by_days(): void
    {
        $personnel = $this->employee(3000);

        $this->substitution($personnel, ['start_date' => '2026-10-20', 'end_date' => null, 'extra_pay_amount' => 310.0]);

        // October: 12 of 31 days → 120; November: the whole month → 310.
        $october = app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-10-31', 2026, 10);
        $this->assertSame(120.0, (float) collect($october['lines'])->firstWhere('code', 'substitution')['amount']);
        $this->assertSame(310.0, (float) $this->calculateNovember($personnel)->lines->firstWhere('code', 'substitution')?->amount);
    }

    public function test_revoking_the_orders_removes_the_earnings_on_the_next_calculation(): void
    {
        $personnel = $this->employee(1680);
        $workId = $this->restDayWork($personnel, '2026-11-08', OrderRestDayWork::COMPENSATION_DOUBLE_PAY);
        $this->substitution($personnel, ['start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'extra_pay_percent' => 10.0], 'order_substitution:9');

        $runs = app(PayrollRunService::class);
        $run = $runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 11), $this->regimeId));
        $this->assertSame(1680.0 + 160.0 + 168.0, (float) $run->payslips()->sole()->gross);

        app(OrderRestDayWork::class)->remove((int) $workId);
        app(OrderCompensationSync::class)->removeSubstitution('order_substitution:9');

        $payslip = $runs->calculate($run->fresh())->payslips()->with('lines')->sole();
        $this->assertSame(1680.0, (float) $payslip->gross);
        $this->assertNull($payslip->lines->firstWhere('code', 'rest_day_work'));
        $this->assertNull($payslip->lines->firstWhere('code', 'substitution'));
    }

    public function test_a_locked_run_is_not_touched_and_a_late_order_is_paid_as_retro(): void
    {
        $personnel = $this->employee(3000);
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);
        $november = $runs->lock($runs->approve($runs->calculate($runs->createRun($periods->createPeriod(2026, 11), $this->regimeId))));
        $lockedNet = (float) $november->payslips()->sole()->net;

        $this->substitution($personnel, ['start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'extra_pay_percent' => 10.0]);

        $this->assertSame($lockedNet, (float) $november->payslips()->sole()->fresh()->net);
        $this->assertSame(0, $november->payslips()->sole()->lines()->where('code', 'substitution')->count());

        $december = $runs->calculate($runs->createRun($periods->createPeriod(2026, 12), $this->regimeId));
        $retro = $december->payslips()->sole()->lines()->where('code', 'retro')->value('amount');
        $this->assertGreaterThan(0.0, (float) $retro, 'The net difference of the locked month is paid as retro.');
        $this->assertLessThan(300.0, (float) $retro, 'Retro is net of the statutory deductions on the 300 gross.');
    }

    public function test_a_run_cannot_lock_when_order_pay_changed_after_its_calculation(): void
    {
        $personnel = $this->employee(1680);
        $workId = $this->restDayWork($personnel, '2026-11-08', OrderRestDayWork::COMPENSATION_DOUBLE_PAY);
        $runs = app(PayrollRunService::class);
        $run = $runs->approve($runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 11), $this->regimeId)));
        $this->assertNotEmpty($run->payslips()->sole()->lines()->where('code', 'rest_day_work')->value('sources'));

        // A substitution approved after the calculation …
        $this->substitution($personnel, ['start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'extra_pay_percent' => 10.0], 'order_substitution:41');
        $this->assertLockRefused($runs, $run);

        $run = $runs->approve($runs->calculate($runs->reopen($run->fresh())));

        // … and a rest-day order revoked after it are both caught.
        app(OrderRestDayWork::class)->remove((int) $workId);
        $this->assertLockRefused($runs, $run);

        $run = $runs->lock($runs->approve($runs->calculate($runs->reopen($run->fresh()))));
        $this->assertSame('locked', $run->status);
        $this->assertSame(168.0, (float) $run->payslips()->sole()->lines()->where('code', 'substitution')->value('amount'));
    }

    public function test_order_pay_revoked_after_its_month_was_paid_is_recovered_once_on_the_next_regular_run(): void
    {
        $personnel = $this->employee(1680);
        $workId = $this->restDayWork($personnel, '2026-11-08', OrderRestDayWork::COMPENSATION_DOUBLE_PAY);
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);
        $november = $runs->lock($runs->approve($runs->calculate($runs->createRun($periods->createPeriod(2026, 11), $this->regimeId))));
        $novemberNet = (float) $november->payslips()->sole()->net;
        $withoutOrderNet = (float) app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-11-30', 2026, 11, true, [])['net'];

        app(OrderRestDayWork::class)->remove((int) $workId);

        $this->assertSame($novemberNet, (float) $november->payslips()->sole()->fresh()->net, 'The locked month itself is not touched.');

        $december = $runs->calculate($runs->createRun($periods->createPeriod(2026, 12), $this->regimeId));
        $payslip = $december->payslips()->sole();
        $recovery = $payslip->lines()->where('code', 'retro_recovery')->first();

        // The net the 160 AZN gross added in November (after its statutory deductions) comes back.
        $this->assertNotNull($recovery);
        $this->assertSame('deduction', $recovery->kind);
        $this->assertSame(__('payroll::dashboard.fields.retro_recovery'), $recovery->name);
        $this->assertEqualsWithDelta($novemberNet - $withoutOrderNet, (float) $recovery->amount, 0.01);
        $this->assertLessThan(160.0, (float) $recovery->amount);
        $this->assertEqualsWithDelta((float) $payslip->gross - (float) $payslip->total_deductions, (float) $payslip->net, 0.01);

        $runs->lock($runs->approve($december));
        $this->assertEqualsWithDelta(-(float) $recovery->amount, (float) RetroPayment::query()->where('source_payroll_run_id', $november->id)->sum('amount'), 0.01);

        $january = $runs->calculate($runs->createRun($periods->createPeriod(2027, 1), $this->regimeId));
        $this->assertSame(0, $january->payslips()->sole()->lines()->whereIn('code', ['retro', 'retro_recovery'])->count(), 'Recovered once, never again.');
    }

    public function test_a_retroactive_pay_cut_without_an_order_change_is_not_recovered(): void
    {
        $personnel = $this->employee(3000);
        $this->substitution($personnel, ['start_date' => '2026-11-01', 'end_date' => '2026-11-30', 'extra_pay_percent' => 10.0]);
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);
        $runs->lock($runs->approve($runs->calculate($runs->createRun($periods->createPeriod(2026, 11), $this->regimeId))));

        app(CompensationService::class)->assignCompensation($personnel->tabel_no, ['regime_id' => $this->regimeId, 'base_amount' => 2500, 'effective_from' => '2026-11-01']);

        $december = $runs->calculate($runs->createRun($periods->createPeriod(2026, 12), $this->regimeId));

        $this->assertSame(0, $december->payslips()->sole()->lines()->whereIn('code', ['retro', 'retro_recovery'])->count());
    }

    public function test_with_finance_owning_payroll_nothing_is_computed_here_and_the_feeds_carry_the_facts(): void
    {
        config(['integration.payroll_owner' => ConfiguredPayrollOwnership::FINANCE]);
        RateLimiter::clear('integration-ip:127.0.0.1');
        $personnel = $this->employee(1680);
        $this->restDayWork($personnel, '2026-11-08', OrderRestDayWork::COMPENSATION_DOUBLE_PAY);
        $this->restDayWork($personnel, '2026-11-15', OrderRestDayWork::COMPENSATION_DAY_OFF);
        $this->substitution($personnel, ['start_date' => CarbonImmutable::now()->startOfMonth()->toDateString(), 'end_date' => null, 'extra_pay_percent' => 25.0, 'substituted_tabel_no' => 'TB-COL', 'substituted_name' => 'Həsənova Leyla Əli']);

        $runs = app(PayrollRunService::class);

        try {
            $runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 11), $this->regimeId));
            $this->fail('A finance-owned installation must not compute payroll.');
        } catch (RuntimeException) {
            $this->assertSame(0, Payslip::query()->count());
        }

        $token = ApiToken::generate('ARBAY test', null)['plain'];

        $attendance = collect($this->withToken($token)->getJson('/api/v1/attendance.month?year=2026&month=11')->assertOk()->json('data.items'))
            ->firstWhere('external_no', $personnel->tabel_no);
        $this->assertSame([
            ['day' => 8, 'date' => '2026-11-08', 'minutes' => 480, 'compensation' => 'double_pay'],
            ['day' => 15, 'date' => '2026-11-15', 'minutes' => 480, 'compensation' => 'day_off'],
        ], $attendance['rest_day_work']);

        $compensation = collect($this->withToken($token)->getJson('/api/v1/compensation')->assertOk()->json('data.items'))
            ->firstWhere('external_no', $personnel->tabel_no);
        $this->assertCount(1, $compensation['substitutions']);
        $this->assertSame('TB-COL', $compensation['substitutions'][0]['substituted_external_no']);
        $this->assertEquals(25, $compensation['substitutions'][0]['extra_pay_percent']);
        $this->assertNull($compensation['substitutions'][0]['end_date']);
    }

    private function assertLockRefused(PayrollRunService $runs, PayrollRun $run): void
    {
        try {
            $runs->lock($run->fresh());
            $this->fail('A run whose order pay changed after the calculation must not lock.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('payroll::dashboard.messages.order_earnings_changed')], $exception->errors()['run']);
        }

        $this->assertSame('approved', $run->fresh()->status);
    }

    private function calculateNovember(Personnel $personnel): Payslip
    {
        $runs = app(PayrollRunService::class);
        $run = $runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 11), $this->regimeId));

        return $run->payslips()->where('tabel_no', $personnel->tabel_no)->with('lines')->firstOrFail();
    }

    private function restDayWork(Personnel $personnel, string $date, string $compensation): ?int
    {
        return app(OrderRestDayWork::class)->record($personnel->tabel_no, CarbonImmutable::parse($date), $compensation, 'Əmr № T-1');
    }

    /**
     * @param  array<string,mixed>  $terms
     */
    private function substitution(Personnel $personnel, array $terms, ?string $sourceKey = null): void
    {
        app(OrderCompensationSync::class)->recordSubstitution($personnel->tabel_no, $terms + ['order_no' => 'T-2'], $sourceKey ?? 'order_substitution:'.Str::random(6));
    }

    private function employee(float $base): Personnel
    {
        $personnel = Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'Jane',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::random(8).'@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-01-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));

        app(CompensationService::class)->assignCompensation($personnel->tabel_no, ['regime_id' => $this->regimeId, 'base_amount' => $base, 'effective_from' => '2026-01-01']);

        return $personnel;
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
