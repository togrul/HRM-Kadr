<?php

namespace Tests\Feature\Compensation;

use App\Models\EmployeeCompensation;
use App\Models\User;
use App\Modules\Compensation\Application\Services\CompanySectorSettings;
use App\Modules\Compensation\Application\Services\CompensationService;
use App\Modules\Compensation\Application\Services\RegimeAlignmentService;
use App\Modules\Compensation\Livewire\Tabs\RegimesTab;
use App\Services\Profiles\ProfileState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Payroll\Concerns\MakesPayrollEmployees;
use Tests\TestCase;

/**
 * Şirkət sektoru və standart rejim (docs/payroll-legal-basis.md §8): VM m.101.1-1 güzəşt cədvəli yalnız
 * neft-qaz sahəsində fəaliyyəti olmayan qeyri-dövlət sektoruna aiddir (meyarlar — NK-nın 18.02.2019
 * tarixli 56 nömrəli Qərarı). Neft-qaz və dövlət şirkətlərinin yeni təyinatları «state» rejimini alır;
 * mövcud işçilər avtomatik dəyişmir — siyahı və təsdiqli toplu düzəliş, kilidli aylara toxunmadan.
 */
class CompanyRegimeTest extends TestCase
{
    use MakesPayrollEmployees;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPayrollReferenceData();
    }

    public function test_sector_maps_to_the_default_regime_of_new_assignments(): void
    {
        // Sektor seçilməyibsə quraşdırma profilindən: «Publik» → dövlət, «Hərbi» → hərbi, qalanları → özəl.
        $this->app->instance(ProfileState::class, new ProfileState([], 'public'));
        $this->assertSame(CompanySectorSettings::STATE, app(CompanySectorSettings::class)->sector());
        $this->app->instance(ProfileState::class, new ProfileState([], 'military'));
        $this->assertSame(CompanySectorSettings::MILITARY, app(CompanySectorSettings::class)->sector());
        $this->app->instance(ProfileState::class, new ProfileState([], 'default'));

        $settings = app(CompanySectorSettings::class);
        $this->assertSame(CompanySectorSettings::PRIVATE_NON_OIL, $settings->sector());
        $this->assertFalse($settings->isExplicit());
        $this->assertSame($this->regimeId('private'), $settings->defaultRegimeId());

        $settings->setSector(CompanySectorSettings::OIL_GAS, 'ARDNŞ-in subpodratçısı, gəlirin 70%-i');
        $this->assertSame($this->regimeId('state'), $settings->defaultRegimeId());
        $this->assertTrue(DB::table('activity_log')->where('event', 'company_sector_changed')->exists());

        $personnel = $this->payrollEmployee($this->regimeId('private'), 1000);
        $draft = app(CompensationService::class)->createDraftForHire($personnel->tabel_no, 1500, 'AZN', now(), 'Ə-1');
        $this->assertSame($this->regimeId('state'), (int) $draft?->regime_id);
    }

    public function test_regime_names_tell_oil_gas_from_non_oil_private(): void
    {
        $this->assertSame('Dövlət və neft-qaz sektoru', DB::table('compensation_regimes')->where('code', 'state')->value('name'));
        $this->assertSame('Özəl qeyri-neft-qaz sektoru', DB::table('compensation_regimes')->where('code', 'private')->value('name'));
    }

    /**
     * Neft-qaz şirkəti: işçi səhvən «private» rejimindədir, iyun kilidlənib. Düzəliş iyulun 1-dən yeni
     * təyinat yaradır; iyun tarixi rədd edilir, iyun payslip-i olduğu kimi qalır.
     */
    public function test_bulk_realign_creates_a_new_assignment_after_the_locked_months(): void
    {
        app(CompanySectorSettings::class)->setSector(CompanySectorSettings::OIL_GAS, 'NK 56, 2.1.1');
        $private = $this->regimeId('private');
        $personnel = $this->payrollEmployee($private, 1100, '2026-01-01', []);
        $june = $this->lockedRun(2026, 6, $private);

        $service = app(RegimeAlignmentService::class);
        $mismatch = collect($service->mismatches(null, '2026-07-15'))->firstWhere('tabel_no', $personnel->tabel_no);
        $this->assertNotNull($mismatch);
        $this->assertSame('2026-07-01', $mismatch['earliest_date']);

        $refused = $service->realign([$personnel->tabel_no], '2026-06-01', 'Neft-qaz sektoru');
        $this->assertSame([], $refused['done']);
        $this->assertArrayHasKey($personnel->tabel_no, $refused['skipped']);

        $result = $service->realign([$personnel->tabel_no], '2026-07-01', 'Neft-qaz sektoru');
        $this->assertSame([$personnel->tabel_no], $result['done']);

        $rows = EmployeeCompensation::query()->where('tabel_no', $personnel->tabel_no)->orderBy('effective_from')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('2026-06-30', $rows[0]->effective_to?->toDateString());
        $this->assertSame($private, (int) $rows[0]->regime_id);
        $this->assertSame($this->regimeId('state'), (int) $rows[1]->regime_id);
        $this->assertSame('1100.00', (string) $rows[1]->base_amount);
        $this->assertTrue(DB::table('activity_log')->where('event', 'regime_realigned')->exists());

        $this->assertSame('949.50', $june->payslips()->sole()->refresh()->net);
        $this->assertSame([], $service->mismatches(null, '2026-07-15'));
    }

    public function test_effective_date_must_be_the_first_of_a_month_and_a_reason_is_required(): void
    {
        $personnel = $this->payrollEmployee($this->regimeId('military'), 1100);

        try {
            app(RegimeAlignmentService::class)->realign([$personnel->tabel_no], '2026-07-15', 'səbəb');
            $this->fail('Ayın ortası qəbul olunmamalıdır.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('alignDate', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        app(RegimeAlignmentService::class)->realign([$personnel->tabel_no], '2026-07-01', ' ');
    }

    public function test_the_tab_lists_mismatches_and_realigns_after_confirmation(): void
    {
        $user = User::factory()->create();
        grantAllStructures($user);
        foreach (['show-compensation', 'manage-compensation'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);
        app(CompanySectorSettings::class)->setSector(CompanySectorSettings::PRIVATE_NON_OIL, 'Özəl şirkət');
        $personnel = $this->payrollEmployee($this->regimeId('state'), 900, '2026-01-01');

        Livewire::test(RegimesTab::class)
            ->assertSee($personnel->tabel_no)
            ->set('selected', [$personnel->tabel_no])
            ->set('alignDate', now()->addMonthNoOverflow()->startOfMonth()->toDateString())
            ->set('alignReason', 'Özəl qeyri-neft şirkəti')
            ->call('realign')
            ->assertHasNoErrors()
            ->assertDontSee($personnel->tabel_no);

        $this->assertSame(
            $this->regimeId('private'),
            (int) EmployeeCompensation::query()->where('tabel_no', $personnel->tabel_no)->where('status', 'active')->value('regime_id'),
        );
    }
}
