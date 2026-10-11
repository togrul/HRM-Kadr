<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationComponent;
use App\Models\EnforcementDocument;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Payroll\Application\Services\EnforcementService;
use App\Modules\Payroll\Application\Services\LoanService;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Modules\Payroll\Livewire\Tabs\EnforcementTab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Payroll\Concerns\MakesPayrollEmployees;
use Tests\TestCase;

/**
 * İcra sənədləri (docs/payroll-legal-basis.md §10): ƏM m.176, «İcra haqqında» m.64, 65, 68, 75.
 *
 * Ümumi nümunə: özəl qeyri-neft 2026, 1 000 AZN, əsas iş yeri. Vergi (1000 − 200) × 3% = 24, DSMF 6 + 800 × 10% = 86,
 * işsizlik 5, tibbi 20 → statutor 135. Tutulma bazası (verilməli olan əmək haqqı) = 865,00.
 * 50 % həddi = 432,50; 70 % həddi = 605,50; 20 % (kredit/könüllü) = 173,00.
 */
class EnforcementPayrollTest extends TestCase
{
    use MakesPayrollEmployees;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPayrollReferenceData();
    }

    public function test_alimony_percentage_is_taken_from_the_wage_after_tax(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1000);
        $this->document($personnel, ['kind' => 'alimony', 'percent' => 25]);

        $calc = $this->calc($personnel);
        $this->assertSame([216.25], $this->enforcementAmounts($calc)); // 865 × 25%
        $this->assertSame(648.75, $calc['net']);
    }

    public function test_caps_are_70_percent_for_alimony_and_50_percent_for_other_orders(): void
    {
        $alimony = $this->payrollEmployee($this->regimeId('private'), 1000);
        $this->document($alimony, ['kind' => 'alimony', 'method' => 'fixed', 'fixed_amount' => 700]);
        $calc = $this->calc($alimony);
        $this->assertSame([605.5], $this->enforcementAmounts($calc));
        $this->assertStringContainsString('700,00', (string) collect($calc['lines'])->firstWhere('code', 'enforcement')['name']);

        $other = $this->payrollEmployee($this->regimeId('private'), 1000);
        $this->document($other, ['kind' => 'other', 'method' => 'fixed', 'fixed_amount' => 700]);
        $this->assertSame([432.5], $this->enforcementAmounts($this->calc($other)));
    }

    /**
     * Aliment (1-ci növbə) 216,25 tam; adi icra sənədi (5-ci növbə) 500 tələb edir: ümumi 70 % həddində qalan
     * 389,25 və öz 50 %-i (432,50) — 389,25 tutulur. Cəmi 605,50 = 70 %.
     */
    public function test_priority_first_then_ordinary_orders_within_the_remaining_room(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1000);
        $this->document($personnel, ['kind' => 'other', 'method' => 'fixed', 'fixed_amount' => 500, 'received_on' => '2026-01-01']);
        $this->document($personnel, ['kind' => 'alimony', 'percent' => 25, 'received_on' => '2026-03-01']);

        $this->assertSame([216.25, 389.25], $this->enforcementAmounts($this->calc($personnel)));
    }

    /** m.75.8: eyni növbədə yer çatmırsa mütənasib — iki aliment 40% (346 + 346 = 692 > 605,50) → 302,75 + 302,75. */
    public function test_same_priority_shares_proportionally(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1000);
        $this->document($personnel, ['kind' => 'alimony', 'percent' => 40]);
        $this->document($personnel, ['kind' => 'alimony', 'percent' => 40]);

        $this->assertSame([302.75, 302.75], $this->enforcementAmounts($this->calc($personnel)));
    }

    /** m.75.6: 5-ci növbədə daxil olma ardıcıllığı — 300 (əvvəl gələn) + 132,50 (50 %-ə qədər qalan). */
    public function test_last_priority_follows_the_order_of_receipt(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1000);
        $this->document($personnel, ['kind' => 'other', 'method' => 'fixed', 'fixed_amount' => 300, 'received_on' => '2026-05-10', 'document_no' => 'B']);
        $this->document($personnel, ['kind' => 'other', 'method' => 'fixed', 'fixed_amount' => 300, 'received_on' => '2026-02-01', 'document_no' => 'A']);

        $lines = collect($this->calc($personnel)['lines'])->where('code', 'enforcement')->values();
        $this->assertStringContainsString('№ A', (string) $lines[0]['name']);
        $this->assertSame([300.0, 132.5], $lines->pluck('amount')->map(fn ($v) => (float) $v)->all());
    }

    /**
     * Kredit taksiti 20 % (173,00) daxilində qalır və icra sənədi ilə birlikdə ümumi 70 %-i keçmir:
     * aliment 216,25 + kredit 173,00 = 389,25 ≤ 605,50.
     */
    public function test_loan_stays_within_20_percent_and_the_overall_cap(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1000);
        $this->document($personnel, ['kind' => 'alimony', 'percent' => 25]);
        app(LoanService::class)->createLoan($personnel->tabel_no, ['principal' => 1000, 'monthly_installment' => 300, 'start_on' => '2026-01-01']);

        $calc = $this->calc($personnel);
        $this->assertSame(173.0, (float) collect($calc['lines'])->firstWhere('code', 'loan')['amount']);
        $this->assertSame(475.75, $calc['net']);

        // Adi icra sənədi 50 %-i tam tutursa kredit üçün yer qalmır.
        $full = $this->payrollEmployee($this->regimeId('private'), 1000);
        $this->document($full, ['kind' => 'other', 'percent' => 50]);
        app(LoanService::class)->createLoan($full->tabel_no, ['principal' => 1000, 'monthly_installment' => 300, 'start_on' => '2026-01-01']);
        $this->assertNull(collect($this->calc($full)['lines'])->firstWhere('code', 'loan'));
    }

    /** m.68.1.4: zərərli əmək şəraitinə görə əlavə (200) tutulma bazasına daxil deyil. */
    public function test_hazard_pay_is_protected_from_enforcement(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1000, '2026-01-01', [
            ['component_id' => CompensationComponent::where('code', 'hazard')->value('id'), 'amount' => 200],
        ]);
        $this->document($personnel, ['kind' => 'alimony', 'percent' => 25]);

        // 1200 − (30 + 106 + 6 + 24) = 1034; baza 1034 − 200 = 834; 25% = 208,50.
        $this->assertSame([208.5], $this->enforcementAmounts($this->calc($personnel)));
    }

    public function test_off_cycle_takes_only_percentage_orders(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('private'), 1000);
        $this->document($personnel, ['kind' => 'alimony', 'percent' => 25]);
        $this->document($personnel, ['kind' => 'other', 'method' => 'fixed', 'fixed_amount' => 100]);

        $offCycle = app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-06-30', 2026, 6, false, null, null, false, false);
        $this->assertCount(1, $this->enforcementAmounts($offCycle ?? []));
    }

    /**
     * Kilid: tutulma tarixçəyə yazılır, qalıq azalır, borc bitəndə sənəd bağlanır; yenidən açma bərpa edir.
     * Bağlanmış sənəd ödənilmiş ayı retro ilə «geri ödəmir».
     */
    public function test_locking_books_history_and_reopen_restores_the_balance(): void
    {
        $regime = $this->regimeId('private');
        $personnel = $this->payrollEmployee($regime, 1000);
        $document = $this->document($personnel, ['kind' => 'other', 'method' => 'fixed', 'fixed_amount' => 500, 'total_debt' => 100]);

        $june = $this->lockedRun(2026, 6, $regime);
        $document->refresh();
        $this->assertSame('0.00', $document->remaining);
        $this->assertSame('closed', $document->status);
        $this->assertSame('100.00', $document->deductions()->sole()->amount);

        $rows = app(EnforcementService::class)->reportRows($june);
        $this->assertSame('100.00', $rows[0]['amount']);
        $this->assertSame('0.00', $rows[0]['remaining']);

        $runs = app(PayrollRunService::class);
        $july = $runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 7), $regime))->payslips()->sole();
        $this->assertSame(0, $july->lines()->whereIn('code', ['enforcement', 'retro'])->count());

        $runs->reopen($june);
        $document->refresh();
        $this->assertSame('100.00', $document->remaining);
        $this->assertSame('active', $document->status);
        $this->assertSame(0, $document->deductions()->count());
    }

    public function test_hr_registers_an_order_and_exports_the_bailiff_list(): void
    {
        $user = User::factory()->create();
        grantAllStructures($user);
        foreach (['show-payroll', 'manage-payroll', 'export-payroll', 'view-compensation-amounts', 'approve-payroll', 'lock-payroll'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);
        $regime = $this->regimeId('private');
        $personnel = $this->payrollEmployee($regime, 1000);

        Livewire::test(EnforcementTab::class)
            ->call('selectPersonnel', $personnel->tabel_no, 'Doe Jane')
            ->call('openForm')
            ->set('documentForm.kind', 'alimony')
            ->set('documentForm.issuer_name', 'Bakı şəhəri Nəsimi rayon Məhkəməsi')
            ->set('documentForm.document_no', 'İV-77')
            ->set('documentForm.recipient_name', 'Doe Anna')
            ->set('documentForm.percent', '25')
            ->set('documentForm.starts_on', '2026-01-01')
            ->call('saveDocument')
            ->assertHasNoErrors()
            ->assertSee('İV-77');

        $this->assertSame(1, EnforcementDocument::query()->where('priority', 1)->count());

        $run = $this->lockedRun(2026, 6, $regime);

        Livewire::test(EnforcementTab::class, ['periodId' => $run->payroll_period_id])
            ->set('reportRunId', $run->id)
            ->assertSee('216,25')
            ->call('exportReport')
            ->assertFileDownloaded('icra-tutulmalari-2026-06.xlsx');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function document(Personnel $personnel, array $overrides): EnforcementDocument
    {
        return app(EnforcementService::class)->create($personnel->tabel_no, $overrides + [
            'issuer_name' => 'Nəsimi rayon icra şöbəsi',
            'document_no' => 'İV-'.random_int(100, 999),
            'received_on' => '2026-01-01',
            'method' => 'percent',
            'recipient_name' => 'Tələbkar',
            'starts_on' => '2026-01-01',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function calc(Personnel $personnel): array
    {
        $calc = app(PayrollCalculator::class)->calculate($personnel->tabel_no, '2026-06-30', 2026, 6);
        $this->assertNotNull($calc);

        return $calc;
    }

    /**
     * @param  array<string, mixed>  $calc
     * @return list<float>
     */
    private function enforcementAmounts(array $calc): array
    {
        return collect($calc['lines'] ?? [])->where('code', 'enforcement')->pluck('amount')->map(fn ($v): float => (float) $v)->values()->all();
    }
}
