<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Livewire\AddPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelEmploymentTermsTest extends TestCase
{
    use RefreshDatabase;

    public function test_step_one_persists_the_contract_and_working_time_terms(): void
    {
        $user = $this->crudUser();
        app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        $this->personalStep('TERMS-1001')
            ->set('personalForm.personnel.contract_type', 'fixed')
            ->set('personalForm.personnel.contract_date', '2020-01-05')
            ->set('personalForm.personnel.probation_unit', 'month')
            ->set('personalForm.personnel.probation_amount', 3)
            ->set('personalForm.personnel.workplace_type', 'primary')
            ->set('personalForm.personnel.working_time_type', 'full')
            ->set('personalForm.personnel.work_schedule', 'five_day')
            ->set('personalForm.personnel.work_hours', [
                'work_start' => '09:00',
                'work_end' => '18:00',
                'lunch_start' => '13:00',
                'lunch_end' => '14:00',
            ])
            ->set('personalForm.personnel.rest_days', ['saturday', 'sunday'])
            ->call('store')
            ->assertHasNoErrors();

        $personnel = Personnel::query()->where('tabel_no', 'TERMS-1001')->sole();

        $this->assertSame('fixed', $personnel->contract_type);
        $this->assertSame('2020-01-05', $personnel->contract_date->toDateString());
        $this->assertSame('month', $personnel->probation_unit);
        $this->assertSame(3, (int) $personnel->probation_amount);
        $this->assertSame('primary', $personnel->workplace_type);
        $this->assertSame('full', $personnel->working_time_type);
        $this->assertSame('five_day', $personnel->work_schedule);
        $this->assertSame('13:00', $personnel->work_hours['lunch_start']);
        $this->assertSame(['saturday', 'sunday'], $personnel->rest_days);
    }

    public function test_a_shift_rota_asks_for_the_hours_of_every_shift_it_runs(): void
    {
        $user = $this->crudUser();
        app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        $this->personalStep('TERMS-1004')
            ->set('personalForm.personnel.work_schedule', 'shift_3')
            ->assertSee(__('personnel::common.labels.shift_start_time', [
                'shift' => __('personnel::common.employment.shift.3'),
            ]))
            ->assertDontSee(__('personnel::common.labels.shift_start_time', [
                'shift' => __('personnel::common.employment.shift.4'),
            ]))
            ->assertDontSee(__('personnel::common.labels.lunch_start_time'));
    }

    public function test_probation_length_is_required_once_a_unit_is_chosen(): void
    {
        $user = $this->crudUser();
        app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        $this->personalStep('TERMS-1002')
            ->set('personalForm.personnel.probation_unit', 'week')
            ->call('store')
            ->assertHasErrors(['personalForm.personnel.probation_amount']);
    }

    public function test_employment_terms_stay_optional(): void
    {
        $user = $this->crudUser();
        app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        $this->personalStep('TERMS-1003')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertNull(Personnel::query()->where('tabel_no', 'TERMS-1003')->sole()->work_schedule);
    }

    /**
     * The wizard on step one, filled in far enough to pass the pre-existing rules.
     */
    private function personalStep(string $tabelNo): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(AddPersonnel::class)
            ->set('step', 1)
            ->set('personalForm.personnel.tabel_no', $tabelNo)
            ->set('personalForm.personnel.name', 'Test')
            ->set('personalForm.personnel.surname', 'User')
            ->set('personalForm.personnel.patronymic', 'Terms')
            ->set('personalForm.personnel.birthdate', '1990-01-01')
            ->set('personalForm.personnel.gender', 1)
            ->set('personalForm.personnel.nationality_id', 1)
            ->set('personalForm.personnel.mobile', '0500000000')
            ->set('personalForm.personnel.pin', 'TRM1234')
            ->set('personalForm.personnel.residental_address', 'Baku')
            ->set('personalForm.personnel.registered_address', 'Baku')
            ->set('personalForm.personnel.education_degree_id', 1)
            ->set('personalForm.personnel.structure_id', 1)
            ->set('personalForm.personnel.position_id', 1)
            ->set('personalForm.personnel.work_norm_id', 1)
            ->set('personalForm.personnel.join_work_date', '2020-01-01');
    }

    private function crudUser(): User
    {
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');
        $user = User::factory()->create();

        foreach (['add-personnels', 'edit-personnels'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }
}
