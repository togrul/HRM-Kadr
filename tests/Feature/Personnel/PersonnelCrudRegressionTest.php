<?php

namespace Tests\Feature\Personnel;

use App\Models\User;
use App\Models\Personnel;
use App\Modules\Personnel\Livewire\AddPersonnel;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use App\Modules\Personnel\Services\PersonnelStepNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelCrudRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_personnel_save_validates_current_draft_step_without_forcing_empty_optional_steps(): void
    {
        $user = $this->crudUser();
        app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        Livewire::test(AddPersonnel::class)
            ->set('step', 5)
            ->set('personalForm.personnel.tabel_no', 'ADD-1001')
            ->set('personalForm.personnel.name', 'Test')
            ->set('personalForm.personnel.surname', 'User')
            ->set('personalForm.personnel.patronymic', 'Crud')
            ->set('personalForm.personnel.birthdate', '1990-01-01')
            ->set('personalForm.personnel.gender', 1)
            ->set('personalForm.personnel.nationality_id', 1)
            ->set('personalForm.personnel.mobile', '0500000000')
            ->set('personalForm.personnel.pin', 'ABC1234')
            ->set('personalForm.personnel.residental_address', 'Baku')
            ->set('personalForm.personnel.registered_address', 'Baku')
            ->set('personalForm.personnel.education_degree_id', 1)
            ->set('personalForm.personnel.structure_id', 1)
            ->set('personalForm.personnel.position_id', 1)
            ->set('personalForm.personnel.work_norm_id', 1)
            ->set('personalForm.personnel.join_work_date', '2020-01-01')
            ->set('historyForm.injury.location', 'Baku')
            ->call('store')
            ->assertHasErrors([
                'historyForm.injury.injury_type',
                'historyForm.injury.date_time',
            ])
            ->assertHasNoErrors([
                'documentForm.document.pin',
                'educationForm.education.educational_institution_id',
            ]);
    }

    public function test_edit_personnel_save_validates_current_draft_step_seven(): void
    {
        $user = $this->crudUser();
        $personnel = app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
            ->call('selectStep', 7)
            ->set('kinshipForm.kinship.fullname', 'Relative')
            ->call('store')
            ->assertHasErrors();
    }

    public function test_step_navigation_service_clamps_step_range(): void
    {
        $service = app(PersonnelStepNavigationService::class);

        $this->assertSame(8, $service->select(999));
        $this->assertSame(1, $service->select(0));
        $this->assertSame(8, $service->next(8));
        $this->assertSame(1, $service->previous(1));
    }

    public function test_child_step_navigation_event_syncs_payload_back_to_parent(): void
    {
        $user = $this->crudUser();
        app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        Livewire::test(AddPersonnel::class)
            ->set('step', 2)
            ->dispatch('personnel-crud:navigate-approved', step: 2, targetStep: 3, payload: [
                'document' => [
                    'pin' => 'SYNC123',
                    'series' => 'AZ',
                    'number' => 123456,
                    'nationality_id' => 1,
                    'born_country_id' => 1,
                    'born_city_id' => 1,
                    'is_married' => true,
                    'height' => 180,
                ],
                'serviceCards' => [],
                'serviceCardsList' => [],
                'passports' => [],
                'passportsList' => [],
            ])
            ->assertSet('step', 3)
            ->assertSet('documentForm.document.pin', 'SYNC123')
            ->assertSet('documentForm.document.series', 'AZ');
    }

    public function test_child_history_step_navigation_syncs_personnel_extra_back_to_parent(): void
    {
        $user = $this->crudUser();
        app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        Livewire::test(AddPersonnel::class)
            ->set('step', 5)
            ->dispatch('personnel-crud:navigate-approved', step: 5, targetStep: 6, payload: [
                'military' => [],
                'militaryList' => [],
                'injury' => [],
                'injuryList' => [],
                'captivity' => [],
                'captivityList' => [],
                'personnelExtra' => [
                    'participation_in_war' => 'Test note',
                ],
            ])
            ->assertSet('step', 6)
            ->assertSet('personalForm.personnelExtra.participation_in_war', 'Test note');
    }

    public function test_child_step_save_event_persists_parent_wizard_state(): void
    {
        $user = $this->crudUser();
        app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        Livewire::test(AddPersonnel::class)
            ->set('step', 2)
            ->set('personalForm.personnel.tabel_no', 'ADD-CHILD-1')
            ->set('personalForm.personnel.name', 'Child')
            ->set('personalForm.personnel.surname', 'Save')
            ->set('personalForm.personnel.patronymic', 'Flow')
            ->set('personalForm.personnel.birthdate', '1990-01-01')
            ->set('personalForm.personnel.gender', 1)
            ->set('personalForm.personnel.nationality_id', 1)
            ->set('personalForm.personnel.mobile', '0500000000')
            ->set('personalForm.personnel.pin', 'CHD1234')
            ->set('personalForm.personnel.residental_address', 'Baku')
            ->set('personalForm.personnel.registered_address', 'Baku')
            ->set('personalForm.personnel.education_degree_id', 1)
            ->set('personalForm.personnel.structure_id', 1)
            ->set('personalForm.personnel.position_id', 1)
            ->set('personalForm.personnel.work_norm_id', 1)
            ->set('personalForm.personnel.join_work_date', '2020-01-01')
            ->dispatch('personnel-crud:save-approved', step: 2, payload: [
                'document' => [
                    'pin' => 'DOC1234',
                    'series' => 'AA',
                    'number' => 55555,
                    'nationality_id' => 1,
                    'born_country_id' => 1,
                    'born_city_id' => 1,
                    'is_married' => false,
                    'height' => 175,
                ],
                'serviceCards' => [],
                'serviceCardsList' => [],
                'passports' => [],
                'passportsList' => [],
            ]);

        $this->assertDatabaseHas((new Personnel)->getTable(), [
            'tabel_no' => 'ADD-CHILD-1',
            'name' => 'Child',
            'surname' => 'Save',
        ]);
    }

    public function test_child_step_save_event_persists_personnel_extra_fields(): void
    {
        $user = $this->crudUser();
        app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);

        Livewire::actingAs($user);

        Livewire::test(AddPersonnel::class)
            ->set('step', 5)
            ->set('personalForm.personnel.tabel_no', 'ADD-CHILD-EXTRA-1')
            ->set('personalForm.personnel.name', 'Extra')
            ->set('personalForm.personnel.surname', 'Payload')
            ->set('personalForm.personnel.patronymic', 'Case')
            ->set('personalForm.personnel.birthdate', '1990-01-01')
            ->set('personalForm.personnel.gender', 1)
            ->set('personalForm.personnel.nationality_id', 1)
            ->set('personalForm.personnel.mobile', '0500000000')
            ->set('personalForm.personnel.pin', 'EXT1234')
            ->set('personalForm.personnel.residental_address', 'Baku')
            ->set('personalForm.personnel.registered_address', 'Baku')
            ->set('personalForm.personnel.education_degree_id', 1)
            ->set('personalForm.personnel.structure_id', 1)
            ->set('personalForm.personnel.position_id', 1)
            ->set('personalForm.personnel.work_norm_id', 1)
            ->set('personalForm.personnel.join_work_date', '2020-01-01')
            ->dispatch('personnel-crud:save-approved', step: 5, payload: [
                'military' => [],
                'militaryList' => [],
                'injury' => [],
                'injuryList' => [],
                'captivity' => [],
                'captivityList' => [],
                'personnelExtra' => [
                    'participation_in_war' => 'War note',
                ],
            ]);

        $this->assertDatabaseHas((new Personnel)->getTable(), [
            'tabel_no' => 'ADD-CHILD-EXTRA-1',
            'participation_in_war' => 'War note',
        ]);
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
