<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationRegime;
use App\Models\PayrollOneOffEarning;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Modules\Payroll\Domain\Contracts\PayrollOneOffEarnings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollOneOffEarningsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_a_handed_over_bonus_is_paid_once_as_taxable_earning_in_the_regular_run(): void
    {
        $personnel = $this->makePersonnel('bonus@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        app(CompensationService::class)->assignCompensation($personnel->tabel_no, ['regime_id' => $regimeId, 'base_amount' => 3000, 'effective_from' => '2026-01-01']);
        $earnings = app(PayrollOneOffEarnings::class);

        $this->assertTrue($earnings->record($personnel->tabel_no, 'kpi_bonus', 'KPI bonusu', 450, 2026, 6, 'performance_bonus:1'));
        $this->assertTrue($earnings->record($personnel->tabel_no, 'kpi_bonus', 'KPI bonusu', 450, 2026, 6, 'performance_bonus:1'));
        $this->assertSame(1, PayrollOneOffEarning::query()->count());

        $runs = app(PayrollRunService::class);
        $run = $runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 6), $regimeId));
        $payslip = $run->payslips()->where('tabel_no', $personnel->tabel_no)->with('lines')->firstOrFail();
        $this->assertSame(450.0, (float) $payslip->lines->firstWhere('code', 'kpi_bonus')?->amount);
        $this->assertSame(3450.0, (float) $payslip->gross);

        $runs->lock($run);
        $this->assertSame($run->id, PayrollOneOffEarning::query()->value('paid_payroll_run_id'));

        // A paid line is final, and a late hand-off for the locked month moves to the next one.
        $earnings->record($personnel->tabel_no, 'kpi_bonus', 'KPI bonusu', 999, 2026, 6, 'performance_bonus:1');
        $this->assertSame(450.0, PayrollOneOffEarning::query()->value('amount'));
        $earnings->record($personnel->tabel_no, 'award', 'Pul mükafatı', 100, 2026, 6, 'order_award:7');
        $this->assertSame(7, (int) PayrollOneOffEarning::query()->where('source_key', 'order_award:7')->value('pay_month'));

        $earnings->withdraw('order_award:7');
        $this->assertDatabaseMissing('payroll_one_off_earnings', ['source_key' => 'order_award:7']);
    }

    public function test_a_run_cannot_lock_when_a_bonus_arrived_after_its_calculation(): void
    {
        $personnel = $this->makePersonnel('late@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        app(CompensationService::class)->assignCompensation($personnel->tabel_no, ['regime_id' => $regimeId, 'base_amount' => 3000, 'effective_from' => '2026-01-01']);
        $runs = app(PayrollRunService::class);
        $run = $runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 6), $regimeId));

        $this->travel(1)->minutes();
        app(PayrollOneOffEarnings::class)->record($personnel->tabel_no, 'kpi_bonus', 'KPI bonusu', 450, 2026, 6, 'performance_bonus:2');

        try {
            $runs->lock($run->fresh());
            $this->fail('A run missing a hand-off must not lock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('run', $exception->errors());
        }
        $this->assertNull(PayrollOneOffEarning::query()->value('paid_payroll_run_id'));

        $runs->lock($runs->calculate($run->fresh()));
        $this->assertSame($run->id, PayrollOneOffEarning::query()->value('paid_payroll_run_id'));
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
