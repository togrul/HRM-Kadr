<?php

namespace Tests\Unit\Forms\Personnel;

use App\Livewire\Forms\Personnel\LaborActivityForm;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Component;
use Tests\TestCase;

class LaborActivityFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_labor_activity_uses_full_structure_path_for_display(): void
    {
        $personnel = $this->makePersonnelWithStructurePath();

        $personnel->laborActivities()->create([
            'company_name' => 'DMX',
            'position' => 'Programçı',
            'coefficient' => null,
            'join_date' => '2026-02-25',
            'leave_date' => null,
            'is_special_service' => false,
            'is_current' => true,
        ]);

        $component = new class extends Component
        {
            public function render()
            {
                return '';
            }
        };

        $form = new LaborActivityForm($component, 'laborActivityForm');
        $form->fillFromModel($personnel->fresh());

        $this->assertSame(
            'DMX / DMX texniki vasitələr idarəsi / DMX texniki müayinə idarəsi 2-ci şöbə',
            $form->laborActivityList[0]['company_name_display']
        );
        $this->assertSame(
            'DMX / DMX texniki vasitələr idarəsi / DMX texniki müayinə idarəsi 2-ci şöbə',
            $personnel->fresh()->structure->fullStructurePath()
        );
    }

    public function test_labor_activities_for_persistence_excludes_display_only_fields(): void
    {
        $component = new class extends Component
        {
            public function render()
            {
                return '';
            }
        };

        $form = new LaborActivityForm($component, 'laborActivityForm');
        $form->laborActivityList = [[
            'company_name' => 'DMX',
            'company_name_display' => 'DMX / Full path',
            'position' => 'Programçı',
            'position_label' => 'Programçı',
            'structure_label' => 'DMX / Full path',
            'position_id' => 1,
            'structure_id' => 101,
            'use_lookup' => true,
            'join_date' => '2026-02-25',
            'time' => '12:00',
            'is_current' => true,
        ]];

        $payload = $form->laborActivitiesForPersistence();

        $this->assertSame('DMX', $payload[0]['company_name']);
        $this->assertSame(1, $payload[0]['position_id']);
        $this->assertSame(101, $payload[0]['structure_id']);
        $this->assertArrayNotHasKey('company_name_display', $payload[0]);
        $this->assertArrayNotHasKey('position_label', $payload[0]);
        $this->assertArrayNotHasKey('structure_label', $payload[0]);
        $this->assertArrayNotHasKey('use_lookup', $payload[0]);
        $this->assertArrayNotHasKey('time', $payload[0]);
    }

    public function test_fill_from_model_restores_lookup_ids_for_current_labor_activity(): void
    {
        $personnel = $this->makePersonnelWithStructurePath();

        $personnel->laborActivities()->create([
            'company_name' => 'DMX',
            'structure_id' => $personnel->structure_id,
            'position' => 'Programçı',
            'position_id' => $personnel->position_id,
            'coefficient' => null,
            'join_date' => '2026-02-25',
            'leave_date' => null,
            'is_special_service' => false,
            'is_current' => true,
        ]);

        $component = new class extends Component
        {
            public function render()
            {
                return '';
            }
        };

        $form = new LaborActivityForm($component, 'laborActivityForm');
        $form->fillFromModel($personnel->fresh());

        $this->assertSame($personnel->position_id, $form->laborActivityList[0]['position_id']);
        $this->assertSame($personnel->structure_id, $form->laborActivityList[0]['structure_id']);
        $this->assertTrue($form->laborActivityList[0]['use_lookup']);
    }

    private function makePersonnelWithStructurePath(): Personnel
    {
        $user = User::factory()->create();

        $country = Country::create(['id' => 1, 'code' => 'AZ']);
        $degree = EducationDegree::create([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bakalavr',
        ]);

        Structure::create([
            'id' => 1,
            'name' => 'DMX',
            'shortname' => 'DMX',
            'parent_id' => null,
            'coefficient' => 1,
            'code' => 1,
            'level' => 0,
        ]);

        $parentStructure = Structure::create([
            'id' => 100,
            'name' => 'DMX texniki vasitələr idarəsi',
            'shortname' => 'TVİ',
            'parent_id' => 1,
            'coefficient' => 1,
            'code' => 10,
            'level' => 1,
        ]);

        $childStructure = Structure::create([
            'id' => 101,
            'name' => 'DMX texniki müayinə idarəsi 2-ci şöbə',
            'shortname' => 'TMİ 2',
            'parent_id' => $parentStructure->id,
            'coefficient' => 1,
            'code' => 11,
            'level' => 2,
        ]);

        $position = Position::create([
            'id' => 1,
            'name' => 'Programçı',
        ]);

        $workNorm = WorkNorm::create([
            'id' => 1,
            'name_az' => 'Full',
            'name_en' => 'Full',
            'name_ru' => 'Full',
        ]);

        return Personnel::withoutEvents(function () use ($user, $country, $degree, $childStructure, $position, $workNorm) {
            return Personnel::factory()->create([
                'tabel_no' => 'TB'.Str::upper(Str::random(6)),
                'surname' => 'Doe',
                'name' => 'John',
                'patronymic' => 'Smith',
                'photo' => null,
                'has_changed_initials' => false,
                'previous_surname' => null,
                'previous_name' => null,
                'previous_patronymic' => null,
                'initials_changed_date' => null,
                'initials_change_reason' => null,
                'birthdate' => '1990-01-01',
                'gender' => 1,
                'phone' => '1234567',
                'mobile' => '7654321',
                'email' => 'john.doe@example.test',
                'nationality_id' => $country->id,
                'has_changed_nationality' => false,
                'previous_nationality_id' => null,
                'nationality_changed_date' => null,
                'nationality_change_reason' => null,
                'pin' => 'P1234567',
                'residental_address' => 'Main street',
                'registered_address' => 'Second street',
                'education_degree_id' => $degree->id,
                'structure_id' => $childStructure->id,
                'position_id' => $position->id,
                'work_norm_id' => $workNorm->id,
                'join_work_date' => '2020-01-01',
                'leave_work_date' => null,
                'social_origin_id' => null,
                'disability_id' => null,
                'disability_given_date' => null,
                'extra_important_information' => null,
                'computer_knowledge' => null,
                'participation_in_war' => null,
                'discrediting_information' => null,
                'scientific_works_inventions' => null,
                'medical_inspection_result' => null,
                'medical_inspection_date' => null,
                'special_inspection_result' => null,
                'special_inspection_date' => null,
                'added_by' => $user->id,
                'deleted_by' => null,
                'is_pending' => false,
                'referenced_by' => null,
            ]);
        });
    }
}
