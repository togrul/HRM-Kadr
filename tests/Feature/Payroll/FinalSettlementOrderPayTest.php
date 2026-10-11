<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationRegime;
use App\Models\EmployeeCompensation;
use App\Models\PayrollOneOffEarning;
use App\Models\PayrollRun;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Attendance\Contracts\OrderRestDayWork;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Modules\Payroll\Domain\Contracts\PayrollOneOffEarnings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

use function grantAllStructures;

/**
 * Xitam olunmuş işçinin əmr əsaslı və birdəfəlik ödənişləri itmir: `Son haqq-hesab` run-ı
 * onları da ödəyir (adi run xitam tarixindən sonra işçini tutmur), ikinci dəfə ödəmir və
 * heç bir run-a düşməyən ödəniş «yetim» kimi görünür.
 */
class FinalSettlementOrderPayTest extends TestCase
{
    use RefreshDatabase;

    private int $regimeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
        $this->regimeId = (int) CompensationRegime::where('code', 'private')->value('id');
        $this->actingAs(grantAllStructures(User::factory()->create()));
    }

    public function test_the_final_settlement_pays_the_leavers_one_off_and_the_next_runs_do_not_pay_it_again(): void
    {
        $leaver = $this->employee(3000);
        $this->leaves($leaver, '2026-06-15');
        app(PayrollOneOffEarnings::class)->record($leaver->tabel_no, 'award', 'Pul mükafatı', 450, 2026, 6, 'order_award:1');

        $runs = app(PayrollRunService::class);
        $period = app(PayrollPeriodService::class)->createPeriod(2026, 6);

        // Adi run xitam olunmuş işçini tutmur — mükafat orada itərdi.
        $regular = $runs->calculate($runs->createRun($period, $this->regimeId));
        $this->assertSame(0, $regular->payslips()->where('tabel_no', $leaver->tabel_no)->count());

        $settlement = $runs->calculate($runs->createRun($period, $this->regimeId, null, 'final_settlement'));
        $payslip = $settlement->payslips()->where('tabel_no', $leaver->tabel_no)->with('lines')->firstOrFail();
        $this->assertSame(450.0, (float) $payslip->lines->firstWhere('code', 'award')?->amount);
        $this->assertNotNull($payslip->lines->firstWhere('code', 'base'));

        $runs->lock($runs->approve($settlement));
        $this->assertSame($settlement->id, PayrollOneOffEarning::query()->sole()->paid_payroll_run_id);

        // Təkrar son haqq-hesab (və adi run) heç nəyi ikinci dəfə ödəmir.
        $again = $runs->calculate($runs->createRun($period, $this->regimeId, null, 'final_settlement'));
        $this->assertSame(0, $again->payslips()->count());
        $this->assertSame(0, $regular->fresh()->payslips()->where('tabel_no', $leaver->tabel_no)->count());
    }

    public function test_an_order_payment_arriving_after_the_termination_month_is_paid_by_a_later_final_settlement_once(): void
    {
        $leaver = $this->employee(3000);
        $other = $this->employee(2000);
        $this->leaves($leaver, '2026-05-20');
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);
        $may = $periods->createPeriod(2026, 5);

        $runs->lock($runs->approve($runs->calculate($runs->createRun($may, $this->regimeId, null, 'final_settlement'))));
        $runs->lock($runs->approve($runs->calculate($runs->createRun($may, $this->regimeId))));

        // Xitamdan sonra imzalanan əmr: bağlı ayın sətri növbəti aya keçir.
        app(PayrollOneOffEarnings::class)->record($leaver->tabel_no, 'award', 'Pul mükafatı', 300, 2026, 5, 'order_award:2');
        $this->assertSame(6, (int) PayrollOneOffEarning::query()->sole()->pay_month);

        $june = $periods->createPeriod(2026, 6);
        $regular = $runs->calculate($runs->createRun($june, $this->regimeId));
        $this->assertSame(0, $regular->payslips()->where('tabel_no', $leaver->tabel_no)->count());
        $this->assertSame(1, $regular->payslips()->where('tabel_no', $other->tabel_no)->count());

        $settlement = $runs->calculate($runs->createRun($june, $this->regimeId, null, 'final_settlement'));
        $payslip = $settlement->payslips()->sole();
        $payslip->load('lines');
        $this->assertSame($leaver->tabel_no, $payslip->tabel_no);
        $this->assertSame(300.0, (float) $payslip->gross);
        $this->assertSame(['award'], $payslip->lines->where('kind', 'earning')->pluck('code')->all());

        $runs->lock($runs->approve($settlement));
        $this->assertSame($settlement->id, PayrollOneOffEarning::query()->sole()->paid_payroll_run_id);

        // Bir də hesablananda ödəniləcək heç nə yoxdur; adi run da götürmür.
        $this->assertSame(0, $runs->calculate($runs->createRun($june, $this->regimeId, null, 'final_settlement'))->payslips()->count());
        $this->assertSame(0, PayrollRun::query()->where('run_type', 'regular')->where('payroll_period_id', $june->id)->first()->payslips()->where('tabel_no', $leaver->tabel_no)->count());
    }

    public function test_the_orphan_check_shows_a_terminated_employees_unpaid_payment_until_a_run_covers_it(): void
    {
        $leaver = $this->employee(3000);
        $active = $this->employee(2000);
        $this->leaves($leaver, '2026-05-20');
        $earnings = app(PayrollOneOffEarnings::class);
        $earnings->record($leaver->tabel_no, 'award', 'Pul mükafatı', 300, 2026, 5, 'order_award:3');
        // Aktiv işçinin ödənişi yetim deyil: adi run onu ödəyəcək.
        $earnings->record($active->tabel_no, 'award', 'Pul mükafatı', 100, 2026, 5, 'order_award:4');

        $runs = app(PayrollRunService::class);
        $orphans = $runs->orphanedOneOffs();
        $this->assertSame(['order_award:3'], $orphans->pluck('source_key')->all());
        $this->assertSame($leaver->tabel_no, $orphans->first()->tabel_no);

        // Hesablanmış son haqq-hesab onu üzərinə götürür — artıq yetim deyil.
        $may = app(PayrollPeriodService::class)->createPeriod(2026, 5);
        $settlement = $runs->calculate($runs->createRun($may, $this->regimeId, null, 'final_settlement'));
        $this->assertSame(0, $runs->orphanedOneOffs()->count());

        // Run silinsə yenə görünür; kilidləndikdən sonra ödənildiyi üçün yox.
        $runs->deleteRun($settlement);
        $this->assertSame(1, $runs->orphanedOneOffs()->count());
        $runs->lock($runs->approve($runs->calculate($runs->createRun($may, $this->regimeId, null, 'final_settlement'))));
        $this->assertSame(0, $runs->orphanedOneOffs()->count());
    }

    public function test_the_final_settlement_pays_the_rest_day_work_of_the_leaving_month_once(): void
    {
        $leaver = $this->employee(1680);
        $this->leaves($leaver, '2026-11-20');
        app(OrderRestDayWork::class)->record($leaver->tabel_no, CarbonImmutable::parse('2026-11-08'), OrderRestDayWork::COMPENSATION_DOUBLE_PAY, 'Əmr № T-1');

        $runs = app(PayrollRunService::class);
        $period = app(PayrollPeriodService::class)->createPeriod(2026, 11);
        $settlement = $runs->calculate($runs->createRun($period, $this->regimeId, null, 'final_settlement'));
        $payslip = $settlement->payslips()->where('tabel_no', $leaver->tabel_no)->with('lines')->firstOrFail();

        $this->assertGreaterThan(0.0, (float) $payslip->lines->firstWhere('code', 'rest_day_work')?->amount);

        // Əmr dəyişikliyi yoxdursa kilidləmə keçir (imza son haqq-hesab üçün də yoxlanılır).
        $runs->lock($runs->approve($settlement));
        $this->assertTrue($settlement->fresh()->isLocked());

        // Eyni ay ikinci dəfə ödənilmir: işçinin maaşını artıq kilidli son haqq-hesab ödəyib.
        $again = $runs->calculate($runs->createRun($period, $this->regimeId, null, 'final_settlement'));
        $this->assertSame(0, $again->payslips()->count());
    }

    public function test_rest_day_work_approved_after_the_settlement_was_locked_is_paid_by_the_next_final_settlement_once(): void
    {
        $leaver = $this->employee(1680);
        $this->leaves($leaver, '2026-11-20');
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);
        $runs->lock($runs->approve($runs->calculate($runs->createRun($periods->createPeriod(2026, 11), $this->regimeId, null, 'final_settlement'))));

        // Xitamdan sonra təsdiqlənən əmr: noyabr artıq son haqq-hesabla bağlanıb.
        app(OrderRestDayWork::class)->record($leaver->tabel_no, CarbonImmutable::parse('2026-11-08'), OrderRestDayWork::COMPENSATION_DOUBLE_PAY, 'Əmr № T-2');

        $december = $periods->createPeriod(2026, 12);
        $settlement = $runs->calculate($runs->createRun($december, $this->regimeId, null, 'final_settlement'));
        $payslip = $settlement->payslips()->with('lines')->sole();

        $this->assertSame($leaver->tabel_no, $payslip->tabel_no);
        $this->assertNull($payslip->lines->firstWhere('code', 'base'));
        $backPay = (float) $payslip->lines->firstWhere('code', 'retro')?->amount;
        $this->assertGreaterThan(0.0, $backPay);
        $this->assertSame($backPay, (float) $payslip->net);

        $runs->lock($runs->approve($settlement));

        $this->assertSame(0, $runs->calculate($runs->createRun($december, $this->regimeId, null, 'final_settlement'))->payslips()->count());
    }

    private function leaves(Personnel $personnel, string $lastDay): void
    {
        $personnel->forceFill(['leave_work_date' => $lastDay])->saveQuietly();
        EmployeeCompensation::query()->where('tabel_no', $personnel->tabel_no)->update(['effective_to' => $lastDay]);
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
