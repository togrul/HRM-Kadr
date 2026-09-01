<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationRegime;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Modules\Payroll\Application\Services\RetroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProrationRetroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_attendance_absence_prorates_pay(): void
    {
        $personnel = $this->makePersonnel('prorate@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $this->assignCompensation($personnel->tabel_no, $regimeId, 3000);
        $this->seedSummary($personnel->tabel_no, 2026, 6, workdays: 22, absenceDays: 2);

        $calc = app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-06-30', 2026, 6);

        $this->assertSame(0.9091, $calc['proration_factor']);
        $this->assertSame(2727.3, $calc['gross']); // 3000 * 20/22
    }

    public function test_no_attendance_summary_means_full_pay(): void
    {
        $personnel = $this->makePersonnel('full@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $this->assignCompensation($personnel->tabel_no, $regimeId, 1000);

        $calc = app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-06-30', 2026, 6);

        $this->assertSame(1.0, $calc['proration_factor']);
        $this->assertSame(1000.0, $calc['gross']);
    }

    public function test_backdated_raise_is_detected_as_pending_retro(): void
    {
        $personnel = $this->makePersonnel('retro@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $this->assignCompensation($personnel->tabel_no, $regimeId, 1000);

        // Pay & lock January at the old rate.
        $period = app(PayrollPeriodService::class)->createPeriod(2026, 1);
        $runService = app(PayrollRunService::class);
        $run = $runService->lock($runService->calculate($runService->createRun($period, $regimeId)));
        $paidNet = (float) $run->payslips()->value('net');

        // Back-date a raise to the same effective date.
        $this->assignCompensation($personnel->tabel_no, $regimeId, 1500);

        $retro = app(RetroService::class)->pendingRetro($personnel->tabel_no);

        $this->assertCount(1, $retro['lines']);
        $this->assertSame('2026-01', $retro['lines'][0]['period_code']);
        $this->assertGreaterThan(0, $retro['total']);
        $this->assertSame($paidNet, $retro['lines'][0]['paid_net']);
    }

    private function assignCompensation(string $tabelNo, int $regimeId, float $base): void
    {
        app(CompensationService::class)->assignCompensation(
            $tabelNo,
            ['regime_id' => $regimeId, 'base_amount' => $base, 'effective_from' => '2026-01-01'],
        );
    }

    private function seedSummary(string $tabelNo, int $year, int $month, int $workdays, int $absenceDays): void
    {
        DB::table('attendance_monthly_summaries')->updateOrInsert(
            ['tabel_no' => $tabelNo, 'year' => $year, 'month' => $month],
            [
                'total_scheduled_minutes' => 0,
                'total_worked_minutes' => 0,
                'total_overtime_minutes' => 0,
                'total_absence_minutes' => 0,
                'total_workdays' => $workdays,
                'total_present_days' => $workdays - $absenceDays,
                'total_absence_days' => $absenceDays,
            ],
        );
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
