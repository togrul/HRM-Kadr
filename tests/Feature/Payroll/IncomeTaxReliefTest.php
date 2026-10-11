<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationComponent;
use App\Models\CompensationRegime;
use App\Models\Personnel;
use App\Models\StatutoryExemption;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Compensation\Livewire\Tabs\StatutoryTab;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Modules\Payroll\Application\Services\StatutoryEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Gəlir vergisi azadolmaları və dövlət/hərbi rejim dərəcələri (docs/payroll-legal-basis.md §5–§6):
 *  - VM m.102.1.6: əsas iş yerində aylıq gəlir 2500 AZN-dək olduqda 200 AZN azaddır — vergi tutulan
 *    gəlirdən çıxılır, 101.1-1 (özəl qeyri-neft) rejimində də tətbiq olunur;
 *  - VM m.101.1 (1-ci cədvəl): dövlət / neft-qaz sektoru 2500-dək 14%, yuxarı 350 + 25%;
 *  - «Sosial sığorta haqqında» m.14.3: 22% / 3%; «Tibbi sığorta haqqında» m.15-10.1.2: 8000-dək 2%;
 *  - VM m.102.1.14.15: hərbi qulluqçunun yalnız vəzifə və rütbə maaşı vergiyə cəlb olunur.
 */
class IncomeTaxReliefTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    /**
     * Özəl qeyri-neft sektoru, 2026 (3% / 10% / 14%) — VM m.101.1-1.1 + m.102.1.6.
     *
     * | Aylıq gəlir | Əvvəl (azadolmasız) | İndi                         |
     * |-------------|---------------------|------------------------------|
     * | 1100        | 33,00               | (1100 − 200) × 3% = 27,00    |
     * | 2400        | 72,00               | (2400 − 200) × 3% = 66,00    |
     * | 2500        | 75,00               | (2500 − 200) × 3% = 69,00    |
     * | 2501        | 75,10               | 75 + 1 × 10% = 75,10 (yoxdur) |
     * | 3000        | 125,00              | 75 + 500 × 10% = 125,00       |
     */
    public function test_200_azn_relief_reduces_the_private_sector_taxable_income_up_to_2500(): void
    {
        $regime = $this->regime('private');

        foreach ([1100 => 27.0, 2400 => 66.0, 2500 => 69.0, 2501 => 75.10, 3000 => 125.0] as $salary => $tax) {
            $personnel = $this->employee($regime, $salary);

            $this->assertSame($tax, $this->line($personnel, 'income_tax_ee'), "Gəlir {$salary} AZN");
        }
    }

    public function test_the_relief_is_shown_on_the_payslip_line_and_only_below_the_limit(): void
    {
        $regime = $this->regime('private');
        $calc = $this->calculate($this->employee($regime, 1100));
        $line = collect($calc['lines'])->firstWhere('code', 'income_tax_ee');

        $this->assertStringContainsString('VM 102.1.6', (string) $line['name']);
        $this->assertStringContainsString('200,00', (string) $line['name']);
        $this->assertSame(949.5, $calc['net']); // 1100 − 27 − 96 − 5,5 − 22

        $above = collect($this->calculate($this->employee($regime, 3000))['lines'])->firstWhere('code', 'income_tax_ee');
        $this->assertStringNotContainsString('102.1.6', (string) $above['name']);
    }

    public function test_the_relief_is_given_only_at_the_main_workplace(): void
    {
        $regime = $this->regime('private');
        $secondary = $this->employee($regime, 1100);
        $secondary->forceFill(['workplace_type' => 'secondary'])->saveQuietly();
        $primary = $this->employee($regime, 1100);
        $primary->forceFill(['workplace_type' => 'primary'])->saveQuietly();

        $this->assertSame(33.0, $this->line($secondary, 'income_tax_ee')); // əlavə iş yeri — 1100 × 3%
        $this->assertSame(27.0, $this->line($primary, 'income_tax_ee'));
    }

    public function test_the_relief_follows_the_2027_and_2028_rates(): void
    {
        $personnel = $this->employee($this->regime('private'), 1100, '2026-01-01');

        $this->assertSame(45.0, $this->line($personnel, 'income_tax_ee', '2027-01-31')); // 900 × 5%
        $this->assertSame(63.0, $this->line($personnel, 'income_tax_ee', '2028-01-31')); // 900 × 7%
    }

    public function test_a_second_payment_in_the_month_gets_no_second_monthly_relief(): void
    {
        $engine = app(StatutoryEngine::class);
        $exemptions = $engine->exemptionsFor(null, '2026-06-30');

        $this->assertSame(200.0, $engine->reliefFor('income_tax', 1100, $exemptions)['amount']);
        $this->assertSame(0.0, $engine->reliefFor('income_tax', 1100, $exemptions, true, false)['amount']);
        $this->assertSame(0.0, $engine->reliefFor('income_tax', 1100, $exemptions, false)['amount']);
        $this->assertSame(0.0, $engine->reliefFor('income_tax', 2500.01, $exemptions)['amount']);
        $this->assertSame(0.0, $engine->reliefFor('dsmf', 1100, $exemptions)['amount']);
    }

    /**
     * Dövlət qulluğu, 2026: VM m.101.1 1-ci cədvəl + m.102.1.6; DSMF m.14.3; tibbi m.15-10.1.2.
     * 1100 AZN: vergi (1100 − 200) × 14% = 126 (özəl cədvəllə 27 idi), DSMF 3% = 33 (86+10 = 96 idi).
     */
    public function test_state_regime_uses_the_public_sector_rates(): void
    {
        $calc = $this->calculate($this->employee($this->regime('state'), 1100));
        $lines = collect($calc['lines'])->keyBy('code');

        $this->assertSame(126.0, (float) $lines['income_tax_ee']['amount']);
        $this->assertSame(33.0, (float) $lines['dsmf_ee']['amount']);
        $this->assertSame(242.0, (float) $lines['dsmf_er']['amount']);
        $this->assertSame(5.5, (float) $lines['unemployment_ee']['amount']);
        $this->assertSame(22.0, (float) $lines['medical_ee']['amount']);
        $this->assertSame(22.0, (float) $lines['medical_er']['amount']);
        $this->assertSame(913.5, $calc['net']);
        $this->assertSame(269.5, $calc['employer_cost']);

        // 10 000 AZN: vergi 350 + 7500 × 25% = 2225; tibbi 8000 × 2% + 2000 × 0,5% = 170.
        $high = collect($this->calculate($this->employee($this->regime('state'), 10000))['lines'])->keyBy('code');
        $this->assertSame(2225.0, (float) $high['income_tax_ee']['amount']);
        $this->assertSame(300.0, (float) $high['dsmf_ee']['amount']);
        $this->assertSame(2200.0, (float) $high['dsmf_er']['amount']);
        $this->assertSame(170.0, (float) $high['medical_ee']['amount']);
    }

    /**
     * Hərbi qulluqçu: vəzifə maaşı 1000 + rütbə maaşı 300 + dil bilik əlavəsi 200. VM m.102.1.14.15 —
     * dil bilik əlavəsi vergidən azaddır: vergi bazası 1300, 102.1.6 ilə 1100 × 14% = 154. Sosial baza
     * (komponent bayraqları) 1500: DSMF 3% = 45, tibbi 2% = 30; işsizlik sığortası yoxdur (ƏM m.6.a).
     */
    public function test_military_pay_is_taxed_only_on_position_and_rank_salary(): void
    {
        $personnel = $this->employee($this->regime('military'), 1000, '2026-01-01', [
            ['component_id' => CompensationComponent::where('code', 'rank_supplement')->value('id'), 'amount' => 300],
            ['component_id' => CompensationComponent::where('code', 'language')->value('id'), 'amount' => 200],
        ]);
        $calc = $this->calculate($personnel);
        $lines = collect($calc['lines'])->keyBy('code');

        $this->assertSame(1500.0, $calc['gross']);
        $this->assertSame(154.0, (float) $lines['income_tax_ee']['amount']);
        $this->assertSame(45.0, (float) $lines['dsmf_ee']['amount']);
        $this->assertSame(330.0, (float) $lines['dsmf_er']['amount']);
        $this->assertSame(30.0, (float) $lines['medical_ee']['amount']);
        $this->assertFalse($lines->has('unemployment_ee'));
        $this->assertFalse($lines->has('unemployment_er'));
    }

    /**
     * Kilidlənmiş ay dəyişmir: seed sətri ən son kilidli aydan sonrakı ayın 1-dən qüvvəyə minir,
     * ona görə həmin ay üzrə retro (geri ödəniş) yaranmır.
     */
    public function test_seeded_relief_starts_after_the_last_locked_month_so_paid_months_are_not_reopened(): void
    {
        DB::table('statutory_exemptions')->delete();
        $regime = $this->regime('private');
        $personnel = $this->employee($regime, 1100);
        $runs = app(PayrollRunService::class);
        $periods = app(PayrollPeriodService::class);
        $june = $runs->lock($runs->approve($runs->calculate($runs->createRun($periods->createPeriod(2026, 6), $regime))));
        $this->assertSame('943.50', $june->payslips()->sole()->net); // azadolmasız: 33 vergi

        (require base_path('app/Modules/Compensation/Database/Migrations/2026_10_11_130000_create_statutory_exemptions_table.php'))->up();

        $this->assertSame('2026-07-01', StatutoryExemption::where('code', 'vm_102_1_6')->sole()->effective_from->toDateString());

        $july = $runs->calculate($runs->createRun($periods->createPeriod(2026, 7), $regime));
        $payslip = $july->payslips()->sole();
        $this->assertSame('949.50', $payslip->net);
        $this->assertSame(0, $payslip->lines()->whereIn('code', ['retro', 'retro_recovery'])->count());
    }

    public function test_an_accountant_can_add_a_new_relief_row_that_closes_the_previous_one(): void
    {
        $user = \App\Models\User::factory()->create();
        foreach (['show-compensation', 'manage-compensation'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);

        Livewire::test(StatutoryTab::class)
            ->call('openPanel', 'exemption')
            ->set('exemptionForm.amount', '250')
            ->set('exemptionForm.income_up_to', '2500')
            ->set('exemptionForm.effective_from', '2027-01-01')
            ->call('saveStatutoryExemption')
            ->assertHasNoErrors()
            ->assertSee('VM 102.1.6');

        $rows = StatutoryExemption::where('code', 'vm_102_1_6')->orderBy('effective_from')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('2026-12-31', $rows[0]->effective_to?->toDateString());
        $this->assertSame('250.00', $rows[1]->amount);

        $personnel = $this->employee($this->regime('private'), 1100);
        $this->assertSame(27.0, $this->line($personnel, 'income_tax_ee', '2026-12-31'));
        $this->assertSame(42.5, $this->line($personnel, 'income_tax_ee', '2027-01-31')); // (1100 − 250) × 5%
    }

    private function regime(string $code): int
    {
        return (int) CompensationRegime::where('code', $code)->value('id');
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function employee(int $regimeId, float $base, string $from = '2026-01-01', array $lines = []): Personnel
    {
        $personnel = $this->makePersonnel();
        app(CompensationService::class)->assignCompensation(
            $personnel->tabel_no,
            ['regime_id' => $regimeId, 'base_amount' => $base, 'effective_from' => $from],
            $lines,
        );

        return $personnel;
    }

    /**
     * @return array<string, mixed>
     */
    private function calculate(Personnel $personnel, string $on = '2026-06-30'): array
    {
        $calc = app(PayrollCalculator::class)->calculate($personnel->tabel_no, $on);
        $this->assertNotNull($calc);

        return $calc;
    }

    private function line(Personnel $personnel, string $code, string $on = '2026-06-30'): float
    {
        return (float) (collect($this->calculate($personnel, $on)['lines'])->firstWhere('code', $code)['amount'] ?? 0.0);
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
            'join_work_date' => '2026-01-01',
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
