<?php

namespace Tests\Feature\Personnel;

use App\Models\User;
use App\Modules\Personnel\Livewire\AddPersonnel;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use App\Modules\Personnel\Services\PersonnelStepNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
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

    private function crudUser(): User
    {
        $user = User::factory()->create();

        foreach (['add-personnels', 'edit-personnels'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }
}
