<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationRegime;
use App\Models\Personnel;
use App\Models\RetroPayment;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RetroAutoPayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_backdated_raise_is_paid_as_retro_line_then_not_paid_again(): void
    {
        $personnel = $this->makePersonnel('retropay@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $compensation = app(CompensationService::class);
        $runService = app(PayrollRunService::class);

        // January paid & locked at base 1000 (net 859 after statutory).
        $compensation->assignCompensation($personnel->tabel_no, ['regime_id' => $regimeId, 'base_amount' => 1000, 'effective_from' => '2026-01-01']);
        $jan = app(PayrollPeriodService::class)->createPeriod(2026, 1);
        $runService->lock($runService->calculate($runService->createRun($jan, $regimeId)));

        // Back-dated raise to 1000 → 1500 effective from January.
        $compensation->assignCompensation($personnel->tabel_no, ['regime_id' => $regimeId, 'base_amount' => 1500, 'effective_from' => '2026-01-01']);

        // February run carries the retro line (net(1500)=1281.50 − net(1000)=859.00 = 422.50).
        $feb = app(PayrollPeriodService::class)->createPeriod(2026, 2);
        $febRun = $runService->calculate($runService->createRun($feb, $regimeId));
        $febPayslip = $febRun->payslips()->with('lines')->first();
        $retroLine = $febPayslip->lines->firstWhere('code', 'retro');
        $this->assertNotNull($retroLine);
        $this->assertSame('422.50', $retroLine->amount);

        // Lock February → retro ledger records the pay-out once.
        $runService->lock($febRun);
        $this->assertSame(1, RetroPayment::where('tabel_no', $personnel->tabel_no)->count());

        // March must NOT pay the same retro again.
        $mar = app(PayrollPeriodService::class)->createPeriod(2026, 3);
        $marRun = $runService->calculate($runService->createRun($mar, $regimeId));
        $marPayslip = $marRun->payslips()->with('lines')->first();
        $this->assertNull($marPayslip->lines->firstWhere('code', 'retro'));
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
