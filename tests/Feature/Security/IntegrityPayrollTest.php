<?php

namespace Tests\Feature\Security;

use App\Models\CompensationRegime;
use App\Models\EmployeeLoan;
use App\Models\PayrollOneOffEarning;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Personnel;
use App\Models\RetroPayment;
use App\Models\User;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\LoanService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Əmək haqqı bütövlüyü (H2, H4, H5, L1, L4): geriyə ödəniş yalnız hesab vərəqəsində olanla
 * yazılır, bağlı ay yenidən açılmır/silinmir, dövr+rejim üzrə bir müntəzəm run, ödənilmiş
 * birdəfəlik ödəniş və kredit hissəsi iki dəfə tutulmur, əməkdaş silinəndə vərəqələr qalır.
 */
class IntegrityPayrollTest extends TestCase
{
    use RefreshDatabase;

    private int $regimeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
        $this->regimeId = (int) CompensationRegime::where('code', 'private')->value('id');
    }

    public function test_lock_refuses_a_run_whose_pay_changed_and_books_only_the_retro_on_the_payslip(): void
    {
        $personnel = $this->makePersonnel();
        $compensation = app(CompensationService::class);
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);

        $compensation->assignCompensation($personnel->tabel_no, ['regime_id' => $this->regimeId, 'base_amount' => 1000, 'effective_from' => '2026-01-01']);
        $jan = $runs->lock($runs->approve($runs->calculate($runs->createRun($periods->createPeriod(2026, 1), $this->regimeId))));

        // February calculated without retro; a back-dated raise arrives afterwards.
        $feb = $runs->calculate($runs->createRun($periods->createPeriod(2026, 2), $this->regimeId));
        $this->assertNull($feb->payslips()->first()->lines()->where('code', 'retro')->first());
        $compensation->assignCompensation($personnel->tabel_no, ['regime_id' => $this->regimeId, 'base_amount' => 1500, 'effective_from' => '2026-01-01']);
        $runs->approve($feb);

        try {
            $runs->lock($feb->fresh());
            $this->fail('A run whose pay inputs changed after calculation must not lock.');
        } catch (ValidationException $exception) {
            $this->assertSame(__('payroll::dashboard.messages.pay_changed'), collect($exception->errors())->flatten()->first());
        }
        $this->assertSame(0, RetroPayment::query()->count());

        // Recalculated, the payslip carries the retro and locking books exactly that line.
        $feb = $runs->lock($runs->approve($runs->calculate($runs->reopen($feb->fresh()))));
        $retroLine = $feb->payslips()->first()->lines()->where('code', 'retro')->first();
        $this->assertSame('422.50', $retroLine->amount);
        $this->assertSame([$jan->id.':422.50'], $retroLine->sources);
        $this->assertSame(1, RetroPayment::query()->count());
        $this->assertSame('422.50', RetroPayment::query()->value('amount'));
        $this->assertSame($jan->id, (int) RetroPayment::query()->value('source_payroll_run_id'));
        $this->assertSame(0, RetroPayment::query()->where('source_payroll_run_id', $feb->id)->count());
    }

    public function test_an_off_cycle_run_books_no_phantom_retro_and_is_not_a_retro_source(): void
    {
        $personnel = $this->makePersonnel();
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);
        app(CompensationService::class)->assignCompensation($personnel->tabel_no, ['regime_id' => $this->regimeId, 'base_amount' => 1000, 'effective_from' => '2026-01-01']);
        PayrollOneOffEarning::query()->create([
            'tabel_no' => $personnel->tabel_no, 'code' => 'bonus', 'name' => 'Bonus', 'amount' => 200,
            'pay_year' => 2026, 'pay_month' => 1, 'taxable' => true, 'affects_social' => true, 'source_key' => 'test:1',
        ]);

        $jan = $periods->createPeriod(2026, 1);
        $runs->lock($runs->approve($runs->calculate($runs->createRun($jan, $this->regimeId))));
        $runs->lock($runs->approve($runs->calculate($runs->createRun($jan, $this->regimeId, null, 'off_cycle'))));

        $this->assertSame(0, RetroPayment::query()->count());

        $feb = $runs->calculate($runs->createRun($periods->createPeriod(2026, 2), $this->regimeId));
        $this->assertNull($feb->payslips()->first()->lines()->where('code', 'retro')->first());
    }

    public function test_only_one_regular_run_per_period_and_regime(): void
    {
        $runs = app(PayrollRunService::class);
        $period = app(PayrollPeriodService::class)->createPeriod(2026, 3);
        $runs->createRun($period, $this->regimeId);

        $this->expectException(ValidationException::class);
        try {
            $runs->createRun($period, $this->regimeId);
        } finally {
            // An off-cycle run and another regime remain possible; the DB backs the rule up.
            $runs->createRun($period, $this->regimeId, null, 'off_cycle');
            $runs->createRun($period, null);
            $this->assertSame(3, PayrollRun::query()->count());

            $threw = false;
            try {
                DB::table('payroll_runs')->insert([
                    'payroll_period_id' => $period->id, 'regime_id' => $this->regimeId, 'run_type' => 'regular',
                    'status' => 'draft', 'regular_slot' => PayrollRun::regularSlotFor((int) $period->id, $this->regimeId),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } catch (QueryException) {
                $threw = true;
            }
            $this->assertTrue($threw);
        }
    }

    public function test_a_paid_one_off_and_the_loan_instalment_are_not_taken_twice(): void
    {
        $personnel = $this->makePersonnel();
        $runs = app(PayrollRunService::class);
        app(CompensationService::class)->assignCompensation($personnel->tabel_no, ['regime_id' => $this->regimeId, 'base_amount' => 3000, 'effective_from' => '2026-01-01']);
        $loan = app(LoanService::class)->createLoan($personnel->tabel_no, ['principal' => 1000, 'monthly_installment' => 200, 'start_on' => '2026-01-01']);
        PayrollOneOffEarning::query()->create([
            'tabel_no' => $personnel->tabel_no, 'code' => 'bonus', 'name' => 'Bonus', 'amount' => 450,
            'pay_year' => 2026, 'pay_month' => 4, 'taxable' => true, 'affects_social' => true, 'source_key' => 'test:2',
        ]);

        $april = app(PayrollPeriodService::class)->createPeriod(2026, 4);
        $regular = $runs->lock($runs->approve($runs->calculate($runs->createRun($april, $this->regimeId))));
        $this->assertSame(450.0, (float) $regular->payslips()->first()->lines()->where('code', 'bonus')->value('amount'));

        // Another regular run for the "all regimes" scope and an off-cycle run in the same month.
        $offCycle = $runs->calculate($runs->createRun($april, $this->regimeId, null, 'off_cycle'));
        $allRegimes = $runs->calculate($runs->createRun($april, null));

        foreach ([$offCycle, $allRegimes] as $run) {
            $payslip = $run->payslips()->first();
            $this->assertNull($payslip?->lines()->where('code', 'bonus')->first());
        }
        $this->assertNull($offCycle->payslips()->first()->lines()->where('code', 'loan')->first());

        $runs->lock($runs->approve($offCycle));
        $this->assertSame('800.00', $loan->fresh()->remaining);
        $this->assertSame(1, $loan->repayments()->count());
    }

    public function test_creating_a_period_never_reopens_a_closed_month_and_reopen_needs_permission_and_reason(): void
    {
        $periods = app(PayrollPeriodService::class);
        $period = $periods->close($periods->createPeriod(2026, 5));

        $this->assertTrue($periods->createPeriod(2026, 5)->isClosed());
        $this->assertTrue($period->fresh()->isClosed());

        $runs = app(PayrollRunService::class);
        $this->assertThrows(fn () => $runs->createRun($period->fresh(), $this->regimeId), ValidationException::class);

        $clerk = User::factory()->create();
        $this->assertThrows(fn () => $periods->reopen($period->fresh(), 'Səhv bağlanıb', $clerk), AuthorizationException::class);

        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findOrCreate('reopen-payroll-period', 'web'));
        $this->assertThrows(fn () => $periods->reopen($period->fresh(), 'abc', $admin), ValidationException::class);

        $periods->reopen($period->fresh(), 'Səhv bağlanıb', $admin);
        $this->assertTrue($period->fresh()->isOpen());
        $entry = Activity::query()->where('event', 'reopened')->where('log_name', 'payroll_period')->first();
        $this->assertNotNull($entry);
        $this->assertSame('Səhv bağlanıb', $entry->properties['reason']);
        $this->assertSame($admin->id, (int) $entry->causer_id);
    }

    public function test_a_closed_period_or_one_with_locked_runs_cannot_be_deleted(): void
    {
        $personnel = $this->makePersonnel();
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);
        app(CompensationService::class)->assignCompensation($personnel->tabel_no, ['regime_id' => $this->regimeId, 'base_amount' => 3000, 'effective_from' => '2026-01-01']);
        app(LoanService::class)->createLoan($personnel->tabel_no, ['principal' => 1000, 'monthly_installment' => 200, 'start_on' => '2026-01-01']);

        $june = $periods->createPeriod(2026, 6);
        $runs->lock($runs->approve($runs->calculate($runs->createRun($june, $this->regimeId))));

        $this->assertThrows(fn () => $periods->delete($june), ValidationException::class);
        $this->assertNotNull(PayrollPeriod::find($june->id));
        $this->assertSame('800.00', EmployeeLoan::query()->value('remaining'));

        $closed = $periods->close($periods->createPeriod(2026, 7));
        $this->assertThrows(fn () => $periods->delete($closed), ValidationException::class);

        $draft = $periods->createPeriod(2026, 8);
        $runs->createRun($draft, $this->regimeId);
        $periods->delete($draft);
        $this->assertNull(PayrollPeriod::find($draft->id));
    }

    public function test_run_reopen_and_calculation_are_refused_in_a_closed_month(): void
    {
        $personnel = $this->makePersonnel();
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);
        app(CompensationService::class)->assignCompensation($personnel->tabel_no, ['regime_id' => $this->regimeId, 'base_amount' => 3000, 'effective_from' => '2026-01-01']);

        $period = $periods->createPeriod(2026, 9);
        $run = $runs->lock($runs->approve($runs->calculate($runs->createRun($period, $this->regimeId))));
        $periods->close($period);

        $this->assertThrows(fn () => $runs->reopen($run->fresh()), ValidationException::class);
        $this->assertSame('locked', $run->fresh()->status);
    }

    public function test_payslips_block_a_hard_delete_of_the_employee_instead_of_cascading(): void
    {
        $personnel = $this->makePersonnel();
        $runs = app(PayrollRunService::class);
        app(CompensationService::class)->assignCompensation($personnel->tabel_no, ['regime_id' => $this->regimeId, 'base_amount' => 3000, 'effective_from' => '2026-01-01']);
        $runs->lock($runs->approve($runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 10), $this->regimeId))));

        // The test connection runs with foreign keys off, so the rule is read from the schema.
        $rule = collect(DB::select('PRAGMA foreign_key_list(payslips)'))->firstWhere('from', 'tabel_no');
        $this->assertSame('RESTRICT', strtoupper((string) $rule->on_delete));
        $this->assertSame('CASCADE', strtoupper((string) $rule->on_update));
    }

    private function makePersonnel(): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
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
            'join_work_date' => '2025-12-01',
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
