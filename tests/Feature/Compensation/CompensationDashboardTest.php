<?php

namespace Tests\Feature\Compensation;

use App\Models\CompensationComponent;
use App\Models\CompensationRegime;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeCompensation;
use App\Models\Personnel;
use App\Modules\Compensation\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompensationDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_dashboard_requires_view_permission(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Dashboard::class)->assertForbidden();
    }

    public function test_authorized_user_can_open_dashboard(): void
    {
        $this->actingAsManager();

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertSet('activeTab', 'scales');
    }

    public function test_manager_can_create_pay_scale(): void
    {
        $this->actingAsManager();
        $regimeId = CompensationRegime::where('code', 'private')->value('id');

        Livewire::test(Dashboard::class)
            ->set('scaleForm.name', 'Mülki şkala 2026')
            ->set('scaleForm.regime_id', $regimeId)
            ->set('scaleForm.currency', 'AZN')
            ->set('scaleForm.effective_from', '2026-01-01')
            ->call('saveScale')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('pay_scales', ['name' => 'Mülki şkala 2026', 'regime_id' => $regimeId]);
    }

    public function test_manager_can_create_component(): void
    {
        $this->actingAsManager();

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'components')
            ->set('componentForm.code', 'transport')
            ->set('componentForm.name', 'Nəqliyyat əlavəsi')
            ->set('componentForm.type', 'earning')
            ->set('componentForm.calc_type', 'fixed')
            ->call('saveComponent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('compensation_components', ['code' => 'transport', 'type' => 'earning']);
    }

    public function test_assigning_new_compensation_ends_the_previous_active_one(): void
    {
        $this->actingAsManager();
        $personnel = $this->makePersonnel('emp1@example.test');
        $regimeId = CompensationRegime::where('code', 'private')->value('id');

        $component = Livewire::test(Dashboard::class)
            ->set('activeTab', 'assignments')
            ->set('selectedTabelNo', $personnel->tabel_no)
            ->set('assignmentForm.regime_id', $regimeId)
            ->set('assignmentForm.base_amount', '1000')
            ->set('assignmentForm.currency', 'AZN')
            ->set('assignmentForm.effective_from', '2026-01-01')
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $component
            ->set('assignmentForm.regime_id', $regimeId)
            ->set('assignmentForm.base_amount', '1200')
            ->set('assignmentForm.currency', 'AZN')
            ->set('assignmentForm.effective_from', '2026-06-01')
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $this->assertSame(1, EmployeeCompensation::where('tabel_no', $personnel->tabel_no)->where('status', 'active')->count());
        $ended = EmployeeCompensation::where('tabel_no', $personnel->tabel_no)->where('status', 'ended')->first();
        $this->assertNotNull($ended);
        $this->assertSame('2026-05-31', $ended->effective_to->toDateString());
        $this->assertSame('1200.00', EmployeeCompensation::where('status', 'active')->value('base_amount'));
    }

    public function test_base_amount_is_masked_without_amounts_permission(): void
    {
        $personnel = $this->makePersonnel('emp2@example.test');
        $comp = EmployeeCompensation::create([
            'tabel_no' => $personnel->tabel_no,
            'regime_id' => CompensationRegime::where('code', 'private')->value('id'),
            'base_amount' => 1500,
            'currency' => 'AZN',
            'effective_from' => '2026-01-01',
            'status' => 'active',
        ]);

        $viewer = \App\Models\User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('show-compensation', 'web'));
        $this->actingAs($viewer);
        $this->assertSame('•••', $comp->maskedBaseAmount());

        $viewer->givePermissionTo(Permission::findOrCreate('view-compensation-amounts', 'web'));
        $viewer->forgetCachedPermissions();
        $this->assertSame('1,500.00', $comp->fresh()->maskedBaseAmount());
    }

    public function test_only_one_primary_bank_account_remains(): void
    {
        $this->actingAsManager();
        $personnel = $this->makePersonnel('emp3@example.test');

        $component = Livewire::test(Dashboard::class)
            ->set('activeTab', 'bank')
            ->set('selectedTabelNo', $personnel->tabel_no)
            ->set('bankForm.iban', 'AZ21NABZ00000000137010001944')
            ->set('bankForm.is_primary', true)
            ->call('saveBank')
            ->assertHasNoErrors();

        $component
            ->set('bankForm.iban', 'AZ21NABZ00000000137010009999')
            ->set('bankForm.is_primary', true)
            ->call('saveBank')
            ->assertHasNoErrors();

        $this->assertSame(1, EmployeeBankAccount::where('tabel_no', $personnel->tabel_no)->where('is_primary', true)->count());
        $this->assertSame(2, EmployeeBankAccount::where('tabel_no', $personnel->tabel_no)->count());
    }

    public function test_manager_can_create_a_statutory_rate(): void
    {
        $this->actingAsManager();

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'statutory')
            ->set('statutoryForm.component_code', 'medical')
            ->set('statutoryForm.payer', 'ee')
            ->set('statutoryForm.base', 'social')
            ->set('statutoryForm.effective_from', '2027-01-01')
            ->set('statutoryBrackets', [['up_to' => 2500, 'rate' => 2], ['up_to' => null, 'rate' => 0.5]])
            ->call('saveStatutoryRate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('statutory_rates', [
            'component_code' => 'medical', 'payer' => 'ee', 'effective_from' => '2027-01-01 00:00:00', 'regime_id' => null,
        ]);
    }

    public function test_validation_messages_use_translated_field_labels(): void
    {
        $this->actingAsManager();

        // Raw attribute path "scale form.name" must NOT leak into the message — the translated label is used.
        Livewire::test(Dashboard::class)
            ->call('saveScale')
            ->assertHasErrors('scaleForm.name')
            ->assertDontSee('scale form.name');
    }

    public function test_seed_catalog_is_available(): void
    {
        $this->assertSame(3, CompensationRegime::count());
        $this->assertSame(11, CompensationComponent::count());
        $this->assertDatabaseHas('compensation_components', ['code' => 'unemployment_ee', 'is_statutory' => true]);
        $this->assertDatabaseHas('compensation_components', ['code' => 'medical_ee', 'is_statutory' => true]);
    }

    private function actingAsManager(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-compensation', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('manage-compensation', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('view-compensation-amounts', 'web'));
        $this->actingAs($user);
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
