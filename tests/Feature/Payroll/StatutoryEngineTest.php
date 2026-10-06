<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationRegime;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use App\Modules\Payroll\Application\Services\StatutoryEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatutoryEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_income_tax_brackets_are_marginal(): void
    {
        $engine = app(StatutoryEngine::class);
        $brackets = [['up_to' => 2500, 'rate' => 3], ['up_to' => 8000, 'rate' => 10], ['up_to' => null, 'rate' => 14]];

        $this->assertSame(33.0, round($engine->applyBrackets(1100, $brackets), 2)); // 1100 * 3%
        $this->assertSame(125.0, round($engine->applyBrackets(3000, $brackets), 2)); // 75 + 50
        $this->assertSame(625.0, round($engine->applyBrackets(8000, $brackets), 2)); // 75 + 550
        $this->assertSame(765.0, round($engine->applyBrackets(9000, $brackets), 2)); // 625 + 140
    }

    public function test_full_statutory_breakdown_for_3000_private(): void
    {
        $personnel = $this->makePersonnel('stat@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');

        app(CompensationService::class)->assignCompensation(
            $personnel->tabel_no,
            ['regime_id' => $regimeId, 'base_amount' => 3000, 'effective_from' => '2026-06-01'],
        );

        $calc = app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-06-30');

        $this->assertSame(3000.0, $calc['gross']);
        $this->assertSame(478.5, $calc['total_deductions']); // tax 125 + dsmf 286 + unemp 15 + medical 52.5
        $this->assertSame(2521.5, $calc['net']);
        $this->assertSame(531.5, $calc['employer_cost']); // dsmf_er 464 + unemp 15 + medical 52.5

        $byCode = collect($calc['lines'])->keyBy('code');
        $this->assertSame(125.0, (float) $byCode['income_tax_ee']['amount']);
        $this->assertSame(286.0, (float) $byCode['dsmf_ee']['amount']);
        $this->assertSame(15.0, (float) $byCode['unemployment_ee']['amount']);
        $this->assertSame(52.5, (float) $byCode['medical_ee']['amount']);
        $this->assertSame('employer', $byCode['dsmf_er']['kind']);
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
