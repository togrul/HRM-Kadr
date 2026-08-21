<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationRegime;
use App\Models\EmployeeBankAccount;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\PayrollExportService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_bank_gl_and_state_exports_build_rows(): void
    {
        $personnel = $this->makePersonnel('export@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');

        app(CompensationService::class)->assignCompensation(
            $personnel->tabel_no,
            ['regime_id' => $regimeId, 'base_amount' => 3000, 'effective_from' => '2026-06-01'],
        );
        EmployeeBankAccount::create([
            'tabel_no' => $personnel->tabel_no,
            'iban' => 'AZ21NABZ00000000137010001944',
            'bank_name' => 'Test Bank',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $period = app(PayrollPeriodService::class)->createPeriod(2026, 6);
        $runService = app(PayrollRunService::class);
        $run = $runService->calculate($runService->createRun($period, $regimeId));

        $export = app(PayrollExportService::class);

        // Bank file: one credit row with the primary IBAN and net amount.
        $bank = collect($export->bankRows($run));
        $this->assertCount(1, $bank);
        $this->assertSame('AZ21NABZ00000000137010001944', $bank->first()['iban']);
        $this->assertSame('2521.50', $bank->first()['amount']); // net for 3000 private (no proration)

        // GL: aggregated by code, includes base + statutory.
        $gl = collect($export->glRows($run));
        $this->assertTrue($gl->contains('code', 'base'));
        $this->assertTrue($gl->contains('code', 'income_tax_ee'));

        // State report: per-employee statutory amounts.
        $state = collect($export->stateRows($run));
        $row = $state->firstWhere('tabel_no', $personnel->tabel_no);
        $this->assertSame('125.00', $row['income_tax']);
        $this->assertSame('286.00', $row['dsmf_ee']);
        $this->assertSame('52.50', $row['medical_er']);
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
