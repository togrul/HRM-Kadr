<?php

namespace Tests\Feature\Payroll\Concerns;

use App\Models\CompensationRegime;
use App\Models\PayrollRun;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Payroll testləri üçün ümumi köməkçilər: istinad məlumatı, işçi, maaş təyinatı, kilidlənmiş run.
 */
trait MakesPayrollEmployees
{
    protected function seedPayrollReferenceData(): void
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

    protected function regimeId(string $code): int
    {
        return (int) CompensationRegime::where('code', $code)->value('id');
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function payrollEmployee(int $regimeId, float $base, string $from = '2026-01-01', array $lines = []): Personnel
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

        app(CompensationService::class)->assignCompensation(
            $personnel->tabel_no,
            ['regime_id' => $regimeId, 'base_amount' => $base, 'effective_from' => $from],
            $lines,
        );

        return $personnel;
    }

    protected function lockedRun(int $year, int $month, ?int $regimeId): PayrollRun
    {
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);

        return $runs->lock($runs->approve($runs->calculate($runs->createRun($periods->createPeriod($year, $month), $regimeId))));
    }
}
