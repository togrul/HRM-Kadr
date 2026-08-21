<?php

namespace Tests\Feature\Payroll;

use App\Models\CompensationComponent;
use App\Models\CompensationRegime;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Personnel;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Modules\Payroll\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayrollRunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_dashboard_requires_view_permission(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
        Livewire::test(Dashboard::class)->assertForbidden();
    }

    public function test_calculate_produces_payslips_with_gross_net_and_lines(): void
    {
        $personnel = $this->makePersonnel('pr1@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $this->assignCompensation($personnel->tabel_no, $regimeId, 1000, 10);

        $period = app(PayrollPeriodService::class)->createPeriod(2026, 6);
        $runService = app(PayrollRunService::class);
        $run = $runService->calculate($runService->createRun($period, $regimeId));

        $this->assertSame('calculated', $run->status);
        $this->assertSame(1, $run->employee_count);
        $this->assertSame('1100.00', $run->gross_total);
        // Statutory deductions are now applied: gross 1100 → net 943.50 (tax 33 + dsmf 96 + unemp 5.5 + medical 22).
        $this->assertSame('943.50', $run->net_total);

        $payslip = $run->payslips()->with('lines')->first();
        $this->assertSame('1100.00', $payslip->gross);
        $this->assertSame('943.50', $payslip->net);
        // base + seniority + 4 employee statutory + 3 employer statutory = 9 lines.
        $this->assertCount(9, $payslip->lines);
        $this->assertTrue($payslip->lines->contains('code', 'income_tax_ee'));
    }

    public function test_locking_freezes_snapshot_and_blocks_recalculation(): void
    {
        $personnel = $this->makePersonnel('pr2@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $this->assignCompensation($personnel->tabel_no, $regimeId, 800, 0);

        $period = app(PayrollPeriodService::class)->createPeriod(2026, 5);
        $runService = app(PayrollRunService::class);
        $run = $runService->calculate($runService->createRun($period, $regimeId));
        $run = $runService->lock($run);

        $this->assertSame('locked', $run->status);
        $payslip = $run->payslips()->first();
        $this->assertSame('locked', $payslip->status);
        $this->assertNotNull($payslip->snapshot);
        $this->assertSame('800.00', (string) $payslip->snapshot['gross']);
        $this->assertNotEmpty($payslip->snapshot['lines']);

        $this->expectException(RuntimeException::class);
        $runService->calculate($run);
    }

    public function test_run_is_scoped_to_its_regime(): void
    {
        $regimePrivate = CompensationRegime::where('code', 'private')->value('id');
        $regimeState = CompensationRegime::where('code', 'state')->value('id');

        $p1 = $this->makePersonnel('priv@example.test');
        $p2 = $this->makePersonnel('state@example.test');
        $this->assignCompensation($p1->tabel_no, $regimePrivate, 1000, 0);
        $this->assignCompensation($p2->tabel_no, $regimeState, 2000, 0);

        $period = app(PayrollPeriodService::class)->createPeriod(2026, 4);
        $runService = app(PayrollRunService::class);
        $run = $runService->calculate($runService->createRun($period, $regimePrivate));

        $this->assertSame(1, $run->employee_count);
        $this->assertSame($p1->tabel_no, $run->payslips()->value('tabel_no'));
    }

    public function test_payslip_amount_is_masked_without_permission(): void
    {
        $personnel = $this->makePersonnel('mask@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $this->assignCompensation($personnel->tabel_no, $regimeId, 1500, 0);

        $period = app(PayrollPeriodService::class)->createPeriod(2026, 3);
        $runService = app(PayrollRunService::class);
        $run = $runService->calculate($runService->createRun($period, $regimeId));
        $payslip = $run->payslips()->first();

        $viewer = \App\Models\User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('show-payroll', 'web'));
        $this->actingAs($viewer);
        $this->assertSame('•••', $payslip->mask($payslip->gross));

        $viewer->givePermissionTo(Permission::findOrCreate('view-compensation-amounts', 'web'));
        $viewer->forgetCachedPermissions();
        $this->assertSame('1,500.00', $payslip->mask($payslip->gross));
    }

    public function test_full_lifecycle_via_livewire(): void
    {
        $personnel = $this->makePersonnel('lc@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $this->assignCompensation($personnel->tabel_no, $regimeId, 1200, 0);

        $user = \App\Models\User::factory()->create();
        foreach (['show-payroll', 'manage-payroll', 'approve-payroll', 'lock-payroll', 'view-compensation-amounts'] as $perm) {
            $user->givePermissionTo(Permission::findOrCreate($perm, 'web'));
        }
        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class)
            ->set('periodForm.year', 2026)
            ->set('periodForm.month', 7)
            ->call('createPeriod')
            ->assertHasNoErrors();

        $periodId = \App\Models\PayrollPeriod::where('code', '2026-07')->value('id');

        $component
            ->set('runForm.payroll_period_id', $periodId)
            ->set('runForm.regime_id', $regimeId)
            ->call('createRun')
            ->assertHasNoErrors();

        $runId = PayrollRun::query()->latest('id')->value('id');

        $component->call('calculateRun', $runId)->call('lockRun', $runId);

        $this->assertSame('locked', PayrollRun::find($runId)->status);
        $this->assertSame(1, Payslip::where('payroll_run_id', $runId)->count());
    }

    public function test_manager_can_delete_payslip_run_and_period(): void
    {
        $personnel = $this->makePersonnel('del@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');
        $this->assignCompensation($personnel->tabel_no, $regimeId, 1000, 0);

        $period = app(PayrollPeriodService::class)->createPeriod(2026, 2);
        $runService = app(PayrollRunService::class);
        $run = $runService->calculate($runService->createRun($period, $regimeId));
        $payslipId = $run->payslips()->value('id');

        $user = \App\Models\User::factory()->create();
        foreach (['show-payroll', 'manage-payroll'] as $perm) {
            $user->givePermissionTo(Permission::findOrCreate($perm, 'web'));
        }
        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class)->call('deletePayslip', $payslipId);
        $this->assertDatabaseMissing('payslips', ['id' => $payslipId]);

        $component->call('deleteRun', $run->id);
        $this->assertDatabaseMissing('payroll_runs', ['id' => $run->id]);

        $component->call('deletePeriod', $period->id);
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    private function assignCompensation(string $tabelNo, int $regimeId, float $base, float $percent): void
    {
        $lines = [];
        if ($percent > 0) {
            $lines[] = ['component_id' => CompensationComponent::where('code', 'seniority')->value('id'), 'percent' => $percent];
        }

        app(CompensationService::class)->assignCompensation(
            $tabelNo,
            ['regime_id' => $regimeId, 'base_amount' => $base, 'effective_from' => '2026-01-01'],
            $lines,
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
