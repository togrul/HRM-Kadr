<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationComponent;
use App\Models\PayrollOneOffEarning;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Payroll\Concerns\MakesPayrollEmployees;
use Tests\TestCase;

/**
 * Hərbi qulluqçunun pul təminatı (docs/payroll-legal-basis.md §6.3):
 *  - gəlir vergisi — yalnız vəzifə və hərbi rütbə maaşı (VM m.102.1.14.15);
 *  - DSMF — «pul təminatı»ndan («Sosial sığorta haqqında» m.15, «Əmək pensiyaları haqqında» m.1.0.5): birdəfəlik
 *    mükafat bazaya düşmür; 22 % / 3 % (m.14.3; m.14.4 pilləsi hərbçiyə aid deyil);
 *  - işsizlikdən sığorta — 0 % (sığortaolunan «əmək müqaviləsi bağlamış» işçidir — m.2.0.3; ƏM m.6.a) ⚠️.
 */
class MilitaryPayStatutoryTest extends TestCase
{
    use MakesPayrollEmployees;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPayrollReferenceData();
    }

    /**
     * Vəzifə maaşı 1 000 + rütbə maaşı 300 + dil əlavəsi 200 + birdəfəlik mükafat 500 = 2 000.
     *  - vergi bazası 1 300 → (1300 − 200) × 14% = 154,00;
     *  - DSMF bazası 1 500 (mükafat xaric) → işçi 45,00, işəgötürən 330,00 (əvvəl: 60,00 / 440,00);
     *  - işsizlik yoxdur.
     */
    public function test_one_off_bonus_is_outside_the_serviceman_tax_and_dsmf_bases(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('military'), 1000, '2026-01-01', [
            ['component_id' => CompensationComponent::where('code', 'rank_supplement')->value('id'), 'amount' => 300],
            ['component_id' => CompensationComponent::where('code', 'language')->value('id'), 'amount' => 200],
        ]);
        PayrollOneOffEarning::query()->create([
            'tabel_no' => $personnel->tabel_no, 'code' => 'bonus', 'name' => 'Mükafat', 'amount' => 500,
            'pay_year' => 2026, 'pay_month' => 6, 'taxable' => true, 'affects_social' => true, 'source_key' => 'test:bonus',
        ]);

        $calc = app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-06-30', 2026, 6);
        $lines = collect($calc['lines'] ?? [])->keyBy('code');

        $this->assertSame(2000.0, $calc['gross'] ?? null);
        $this->assertSame(154.0, (float) $lines['income_tax_ee']['amount']);
        $this->assertSame(45.0, (float) $lines['dsmf_ee']['amount']);
        $this->assertSame(330.0, (float) $lines['dsmf_er']['amount']);
        $this->assertFalse($lines->has('unemployment_ee'));
        $this->assertFalse($lines->has('unemployment_er'));
    }

    /** Özəl rejimdə həmin mükafat DSMF bazasına düşür — qayda yalnız hərbi rejimdədir. */
    public function test_the_dsmf_rule_applies_only_to_the_military_regime(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('state'), 1000);
        PayrollOneOffEarning::query()->create([
            'tabel_no' => $personnel->tabel_no, 'code' => 'bonus', 'name' => 'Mükafat', 'amount' => 500,
            'pay_year' => 2026, 'pay_month' => 6, 'taxable' => true, 'affects_social' => true, 'source_key' => 'test:bonus2',
        ]);

        $lines = collect(app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-06-30', 2026, 6)['lines'] ?? [])->keyBy('code');
        $this->assertSame(45.0, (float) $lines['dsmf_ee']['amount']); // 1500 × 3%
    }
}
