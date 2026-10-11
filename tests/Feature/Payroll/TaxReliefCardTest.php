<?php

namespace Tests\Feature\Payroll;

use App\Models\EmployeeTaxRelief;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Compensation\Application\Services\TaxReliefService;
use App\Modules\Compensation\Livewire\Tabs\ReliefsTab;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Payroll\Concerns\MakesPayrollEmployees;
use Tests\TestCase;

/**
 * İşçi üzrə gəlir vergisi güzəşti kartı (docs/payroll-legal-basis.md §9):
 *  - VM m.102.1-1 (800), 102.2 (400), 102.3 (200), 102.4 (100), 102.5 (50) — vergi tutulan aylıq gəlir azaldılır;
 *  - m.102.7 — 102.1-1/102.2/102.3/102.4 üzrə yalnız ən böyüyü; 102.1.6 (200, ≤ 2500) və 102.5 üstəgəl;
 *  - m.102.8 — yalnız əsas iş yerində; status işə qəbulda var idisə — işə qəbul tarixindən;
 *  - kilidlənmiş aylar yenidən hesablanmır (payroll_from), retro yaranmır.
 */
class TaxReliefCardTest extends TestCase
{
    use MakesPayrollEmployees;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPayrollReferenceData();
    }

    /**
     * Özəl qeyri-neft, 2026 (3%), əsas iş yeri, 1 100 AZN:
     *
     * | Kartlar               | Azad olunan            | Vergi                 |
     * |-----------------------|------------------------|-----------------------|
     * | yoxdur                | 200 (102.1.6)          | 900 × 3% = 27,00      |
     * | 102.3                 | 200 + 200              | 700 × 3% = 21,00      |
     * | 102.2 + 102.3         | 200 + max(400,200)=600 | 500 × 3% = 15,00      |
     * | 102.2 + 102.3 + 102.5 | 600 + 50 = 650         | 450 × 3% = 13,50      |
     * | 102.1-1 + 102.2       | 200 + 800 = 1000       | 100 × 3% = 3,00       |
     */
    public function test_relief_cards_reduce_taxable_income_and_only_the_largest_of_102_7_counts(): void
    {
        $cases = [
            [[], 27.0],
            [['vm_102_3'], 21.0],
            [['vm_102_2', 'vm_102_3'], 15.0],
            [['vm_102_2', 'vm_102_3', 'vm_102_5'], 13.5],
            [['vm_102_1_1', 'vm_102_2'], 3.0],
        ];

        foreach ($cases as [$cards, $tax]) {
            $personnel = $this->payrollEmployee($this->regimeId('private'), 1100);
            foreach ($cards as $card) {
                $this->card($personnel, $card);
            }

            $this->assertSame($tax, $this->tax($personnel), 'Kartlar: '.implode(', ', $cards));
        }
    }

    public function test_payslip_line_names_every_applied_relief(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1100);
        $this->card($personnel, 'vm_102_2');
        $this->card($personnel, 'vm_102_3');

        $line = collect($this->calc($personnel)['lines'])->firstWhere('code', 'income_tax_ee');

        $this->assertStringContainsString('600,00', (string) $line['name']);
        $this->assertStringContainsString('VM 102.1.6', (string) $line['name']);
        $this->assertStringContainsString('VM 102.2', (string) $line['name']);
        $this->assertStringNotContainsString('VM 102.3', (string) $line['name']); // 102.7: kiçik olan verilmir
    }

    /** 3 000 AZN — 102.1.6 yoxdur: (3000 − 400) → 75 + 100 × 10% = 85,00 (kartsız 125,00). */
    public function test_relief_above_2500_and_in_the_state_regime(): void
    {
        $high = $this->payrollEmployee($this->regimeId('private'), 3000);
        $this->card($high, 'vm_102_2');
        $this->assertSame(85.0, $this->tax($high));

        // Dövlət/neft-qaz sektoru, 1 100 AZN, 102.3: (1100 − 200 − 200) × 14% = 98,00 (kartsız 126,00).
        $state = $this->payrollEmployee($this->regimeId('state'), 1100);
        $this->card($state, 'vm_102_3');
        $this->assertSame(98.0, $this->tax($state));
    }

    public function test_no_relief_at_a_secondary_workplace_or_in_an_off_cycle_payment(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1100);
        $this->card($personnel, 'vm_102_2');
        $personnel->forceFill(['workplace_type' => 'secondary'])->saveQuietly();

        $this->assertSame(33.0, $this->tax($personnel)); // 1100 × 3%, nə 102.1.6, nə 102.2

        $main = $this->payrollEmployee($this->regimeId('private'), 1100);
        $this->card($main, 'vm_102_2');
        $offCycle = app(PayrollCalculator::class)->calculate($main->tabel_no, '2026-11-30', 2026, 11, false, null, null, false, false);
        $this->assertSame(33.0, (float) collect($offCycle['lines'] ?? [])->firstWhere('code', 'income_tax_ee')['amount']);
    }

    public function test_card_runs_only_within_its_dates(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1100);
        $this->card($personnel, 'vm_102_3', '2026-12-10', '2026-12-10', '2027-02-15');

        $this->assertSame(27.0, $this->tax($personnel, '2026-11-30', 2026, 11));
        $this->assertSame(21.0, $this->tax($personnel, '2026-12-31', 2026, 12)); // natamam ay — tam məbləğ
        $this->assertSame(35.0, $this->tax($personnel, '2027-02-28', 2027, 2)); // 2027: (1100 − 400) × 5%
        $this->assertSame(45.0, $this->tax($personnel, '2027-03-31', 2027, 3)); // bitib: 900 × 5%
    }

    /** VM m.102.8: status işə qəbul tarixində var idisə — işə qəbul tarixindən, sonra yaranıbsa — sənəd tarixindən. */
    public function test_legal_start_follows_article_102_8(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1100); // işə qəbul 01.01.2026

        $existing = $this->card($personnel, 'vm_102_3', '2020-05-01', '2026-03-15');
        $this->assertSame('2026-01-01', $existing->legal_from->toDateString());

        $later = $this->card($personnel, 'vm_102_4', '2026-02-10', '2026-03-15');
        $this->assertSame('2026-03-15', $later->legal_from->toDateString());
    }

    /**
     * Kilidlənmiş iyun ayı dəyişmir: kart iyunda qanunən qüvvədə olsa da maaşda iyulun 1-dən tətbiq
     * olunur, iyul run-unda retro sətri yaranmır.
     */
    public function test_locked_months_are_not_reopened_by_a_new_card(): void
    {
        $regime = $this->regimeId('private');
        $personnel = $this->payrollEmployee($regime, 1100);
        $june = $this->lockedRun(2026, 6, $regime);
        $this->assertSame('949.50', $june->payslips()->sole()->net);

        $card = $this->card($personnel, 'vm_102_3', '2025-01-01', '2026-07-05');
        $this->assertSame('2026-01-01', $card->legal_from->toDateString());
        $this->assertSame('2026-07-01', $card->payroll_from->toDateString());

        $runs = app(PayrollRunService::class);
        $july = $runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 7), $regime));
        $payslip = $july->payslips()->sole();

        $this->assertSame(21.0, (float) $payslip->lines()->where('code', 'income_tax_ee')->value('amount'));
        $this->assertSame(0, $payslip->lines()->whereIn('code', ['retro', 'retro_recovery'])->count());

        // Hələ heç bir kilidli aya düşməyən kart silinə bilər.
        app(TaxReliefService::class)->delete($card);
        $this->assertSame(0, EmployeeTaxRelief::query()->count());
    }

    public function test_a_card_used_in_a_locked_month_cannot_be_deleted_or_ended_inside_it(): void
    {
        $regime = $this->regimeId('private');
        $personnel = $this->payrollEmployee($regime, 1100);
        $card = $this->card($personnel, 'vm_102_3');
        $this->assertSame('955.50', $this->lockedRun(2026, 6, $regime)->payslips()->sole()->net); // 1100 − 21 − 96 − 5,5 − 22

        $service = app(TaxReliefService::class);

        try {
            $service->delete($card);
            $this->fail('Kilidli ayda istifadə olunmuş kart silinməməlidir.');
        } catch (ValidationException) {
            $this->assertSame(1, EmployeeTaxRelief::query()->count());
        }

        $this->expectException(ValidationException::class);
        $service->end($card, '2026-05-31', 'Səhv');
    }

    public function test_hr_can_add_a_card_with_a_private_document_and_end_it(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        grantAllStructures($user);
        foreach (['show-compensation', 'manage-compensation'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1100);

        Livewire::test(ReliefsTab::class, ['tabelNo' => $personnel->tabel_no])
            ->call('openPanel', 'relief')
            ->set('reliefForm.category', 'vm_102_3')
            ->set('reliefForm.document_no', 'TEKO-123')
            ->set('reliefForm.status_from', '2026-01-01')
            ->set('reliefForm.submitted_on', '2026-01-05')
            ->set('document', UploadedFile::fake()->create('arayis.pdf', 20, 'application/pdf'))
            ->call('saveRelief')
            ->assertHasNoErrors()
            ->assertSee('TEKO-123');

        $relief = EmployeeTaxRelief::query()->sole();
        $this->assertNotNull($relief->document_path);
        Storage::disk('local')->assertExists((string) $relief->document_path);
        $this->get(route('compensation.tax-relief.document', $relief))->assertOk();

        Livewire::test(ReliefsTab::class, ['tabelNo' => $personnel->tabel_no])
            ->call('startEnd', $relief->id)
            ->set('endDate', '2026-12-31')
            ->set('endReason', 'Arayışın müddəti bitdi')
            ->call('saveEnd')
            ->assertHasNoErrors();

        $this->assertSame('2026-12-31', $relief->refresh()->valid_to?->toDateString());
    }

    private function card(Personnel $personnel, string $category, string $statusFrom = '2026-01-01', string $submittedOn = '2026-01-01', ?string $validTo = null): EmployeeTaxRelief
    {
        return app(TaxReliefService::class)->create($personnel->tabel_no, [
            'category' => $category,
            'document_no' => 'D-'.$category,
            'status_from' => $statusFrom,
            'submitted_on' => $submittedOn,
            'valid_to' => $validTo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function calc(Personnel $personnel, string $on = '2026-06-30', ?int $year = 2026, ?int $month = 6): array
    {
        $calc = app(PayrollCalculator::class)->calculate($personnel->tabel_no, $on, $year, $month);
        $this->assertNotNull($calc);

        return $calc;
    }

    private function tax(Personnel $personnel, string $on = '2026-06-30', ?int $year = 2026, ?int $month = 6): float
    {
        return (float) (collect($this->calc($personnel, $on, $year, $month)['lines'])->firstWhere('code', 'income_tax_ee')['amount'] ?? 0.0);
    }
}
