<?php

namespace Tests\Feature\Leaves;

use App\Models\User;
use App\Modules\Leaves\Livewire\AddLeave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeavePersonnelSearchIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_and_assigned_searches_are_isolated(): void
    {
        $this->actingAs($this->userWithCreatePermission());
        $this->seedPersonnelSupportTables();

        $this->insertPersonnel(101, 'AP-001', 'Applicant', 'One');
        $this->insertPersonnel(102, 'AS-001', 'Assigned', 'Two');

        $component = Livewire::test(AddLeave::class)
            ->set('personnelName', 'Applicant')
            ->set('assignedSearch', 'Assigned');

        $applicantResults = $component->instance()->applicantPersonnelList;
        $assignedResults = $component->instance()->assignedPersonnelList;

        $this->assertSame(['AP-001'], $applicantResults->pluck('tabel_no')->all());
        $this->assertSame(['AS-001'], $assignedResults->pluck('tabel_no')->all());
    }

    private function userWithCreatePermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-leaves', 'web'));

        return $user;
    }

    private function seedPersonnelSupportTables(): void
    {
        DB::table('countries')->insert([
            'id' => 1,
            'code' => 'AZ',
        ]);

        DB::table('education_degrees')->insert([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Бакалавр',
        ]);

        DB::table('structures')->insert([
            'id' => 1,
            'name' => 'Leaves Structure',
            'shortname' => 'LS',
            'parent_id' => 99,
        ]);

        DB::table('positions')->insert([
            'id' => 1,
            'name' => 'Specialist',
        ]);

        DB::table('work_norms')->insert([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Полный день',
        ]);
    }

    private function insertPersonnel(int $id, string $tabelNo, string $name, string $surname): void
    {
        DB::table('personnels')->insert([
            'id' => $id,
            'tabel_no' => $tabelNo,
            'surname' => $surname,
            'name' => $name,
            'patronymic' => 'Test',
            'has_changed_initials' => false,
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '0501234567',
            'nationality_id' => 1,
            'has_changed_nationality' => false,
            'pin' => 'PIN'.$id,
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            'is_pending' => false,
        ]);
    }
}
