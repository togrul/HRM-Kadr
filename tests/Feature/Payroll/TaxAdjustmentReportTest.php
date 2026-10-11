<?php

namespace Tests\Feature\Payroll;

use App\Models\PayrollOneOffEarning;
use App\Models\User;
use App\Modules\Compensation\Application\Services\RegimeAlignmentService;
use App\Modules\Compensation\Application\Services\TaxReliefService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Modules\Payroll\Application\Services\TaxAdjustmentReportService;
use App\Modules\Payroll\Livewire\Tabs\TaxAdjustmentTab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Payroll\Concerns\MakesPayrollEmployees;
use Tests\TestCase;

/**
 * «Vergi düzəliş hesabatı» (docs/payroll-legal-basis.md §11): kilidlənmiş aylar yaddaşda cari qaydalarla
 * yenidən hesablanır, heç nə dəyişmir; artıq tutulmuş məbləğin qaytarılması ayrıca, açıq əməliyyatdır.
 */
class TaxAdjustmentReportTest extends TestCase
{
    use MakesPayrollEmployees;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPayrollReferenceData();
        Carbon::setTestNow('2026-10-11 10:00:00');

        // Real quraşdırmadakı kimi: 102.1.6 seed sətri kilidli aylardan sonra (iyul) qüvvəyə minib.
        DB::table('statutory_exemptions')->where('code', 'vm_102_1_6')->update(['effective_from' => '2026-07-01']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Özəl qeyri-neft, 1 100 AZN, iyun kilidli (102.1.6 verilməyib, vergi 33,00). Sonra 102.3 kartı
     * (status 2025-dən → qanuni başlanğıc 01.01.2026). Düzgün vergi: (1100 − 200 − 200) × 3% = 21,00 → artıq 12,00.
     */
    public function test_missed_reliefs_show_as_over_withheld_tax_without_touching_the_payslip(): void
    {
        $regime = $this->regimeId('private');
        $personnel = $this->payrollEmployee($regime, 1100);
        $june = $this->lockedRun(2026, 6, $regime);
        $payslip = $june->payslips()->sole();
        $this->assertSame('943.50', $payslip->net);

        app(TaxReliefService::class)->create($personnel->tabel_no, ['category' => 'vm_102_3', 'document_no' => 'T-1', 'status_from' => '2025-03-01', 'submitted_on' => '2026-10-01']);

        $rows = app(TaxAdjustmentReportService::class)->build('2026-01', '2026-09');
        $this->assertCount(1, $rows);
        $row = $rows[0];

        $this->assertSame(33.0, $row['income_tax_ee_paid']);
        $this->assertSame(21.0, $row['income_tax_ee_now']);
        $this->assertSame(12.0, $row['income_tax_ee_diff']);
        $this->assertSame(0.0, $row['dsmf_ee_diff']);
        $this->assertSame('vm_102_3', $row['reliefs']);
        $this->assertSame('943.50', $payslip->refresh()->net); // yalnız oxu
    }

    /**
     * Neft-qaz şirkəti, işçi səhvən özəl rejimdə ödənilib (iyun), sonra iyuldan «state» rejiminə keçirilib.
     * İyun üçün düzgün: vergi (1100 − 200) × 14% = 126 (tutulmuş 33 → az 93); DSMF işçi 3% = 33 (tutulmuş
     * 6 + 900 × 10% = 96 → artıq 63); DSMF işəgötürən 22% = 242 (ödənilmiş 44 + 900 × 15% = 179 → az 63).
     */
    public function test_regime_differences_are_priced_with_the_employees_current_regime(): void
    {
        $private = $this->regimeId('private');
        $personnel = $this->payrollEmployee($private, 1100);
        $this->lockedRun(2026, 6, $private);
        app(RegimeAlignmentService::class)->realign([$personnel->tabel_no], '2026-07-01', 'Neft-qaz subpodratçısı (NK 56, 2.1.2.1)', $this->regimeId('state'));

        $row = app(TaxAdjustmentReportService::class)->build('2026-06', '2026-06')[0];

        $this->assertSame(-93.0, $row['income_tax_ee_diff']);
        $this->assertSame(63.0, $row['dsmf_ee_diff']);
        $this->assertSame(-63.0, $row['dsmf_er_diff']);
        $this->assertSame(0.0, $row['unemployment_ee_diff']);
        $this->assertSame(0.0, $row['medical_ee_diff']);
        $this->assertSame($this->regimeId('state'), $row['target_regime_id']);
    }

    /**
     * Qaytarma: yalnız seçilmiş işçi tərəfi komponentlərin müsbət fərqi, növbəti açıq ayın adi run-una
     * vergidən kənar birdəfəlik ödəniş kimi. Təkrar çağırış dublikat yaratmır.
     */
    public function test_refund_goes_to_the_next_run_as_a_non_taxable_one_off(): void
    {
        $regime = $this->regimeId('private');
        $personnel = $this->payrollEmployee($regime, 1100);
        $payslip = $this->lockedRun(2026, 6, $regime)->payslips()->sole();
        app(TaxReliefService::class)->create($personnel->tabel_no, ['category' => 'vm_102_3', 'document_no' => 'T-1', 'status_from' => '2025-03-01', 'submitted_on' => '2026-10-01']);

        $service = app(TaxAdjustmentReportService::class);
        $result = $service->recordRefunds([(int) $payslip->id], ['income_tax_ee'], 2026, 6, 'Dəqiqləşdirilmiş bəyannamə 10.2026');
        $this->assertSame(['recorded' => 1, 'total' => 12.0], $result);
        $service->recordRefunds([(int) $payslip->id], ['income_tax_ee'], 2026, 7, 'Təkrar');

        $oneOff = PayrollOneOffEarning::query()->sole();
        $this->assertSame(12.0, $oneOff->amount);
        $this->assertSame(7, $oneOff->pay_month); // iyun kilidlidir — növbəti aya keçdi
        $this->assertFalse($oneOff->taxable);
        $this->assertTrue(DB::table('activity_log')->where('event', 'tax_adjustment_refund_recorded')->exists());

        $runs = app(PayrollRunService::class);
        $july = $runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 7), $regime))->payslips()->sole();
        $this->assertSame(12.0, (float) $july->lines()->where('code', TaxAdjustmentReportService::REFUND_CODE)->value('amount'));
        // İyul: 102.1.6 + 102.3 → vergi 21; qaytarma vergiyə cəlb olunmur.
        $this->assertSame(21.0, (float) $july->lines()->where('code', 'income_tax_ee')->value('amount'));
        $this->assertSame(true, $service->build('2026-06', '2026-06')[0]['refund_recorded']);
    }

    public function test_the_tab_needs_amount_permission_and_exports_xlsx(): void
    {
        $user = User::factory()->create();
        grantAllStructures($user);
        foreach (['show-payroll', 'export-payroll'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);

        Livewire::test(TaxAdjustmentTab::class)->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate('view-compensation-amounts', 'web'));
        $regime = $this->regimeId('private');
        $this->payrollEmployee($regime, 1100);
        $this->lockedRun(2026, 6, $regime);
        DB::table('statutory_exemptions')->where('code', 'vm_102_1_6')->update(['effective_from' => '2026-01-01']);

        Livewire::test(TaxAdjustmentTab::class)
            ->set('fromMonth', '2026-01')
            ->set('toMonth', '2026-09')
            ->call('generate')
            ->assertSee('33,00 → 27,00')
            ->call('export')
            ->assertFileDownloaded('vergi-duzelis-2026-01-2026-09.xlsx');
    }
}
