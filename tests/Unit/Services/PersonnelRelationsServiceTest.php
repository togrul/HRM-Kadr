<?php

namespace Tests\Unit\Services;

use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Services\PersonnelRelationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonnelRelationsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_current_labor_activity_lookup_ids_to_personnel_and_persists_them(): void
    {
        $personnel = $this->makePersonnel();

        $newStructure = Structure::query()->create([
            'id' => 501,
            'name' => 'Yeni struktur',
            'shortname' => 'YS',
            'parent_id' => null,
            'coefficient' => 1,
            'code' => 501,
            'level' => 1,
        ]);

        $newPosition = Position::query()->create([
            'id' => 501,
            'name' => 'Şöbə müdiri',
            'approval_rank' => 2,
            'is_approval_target' => true,
        ]);

        app(PersonnelRelationsService::class)->update($personnel, [
            'labor_activities' => [[
                'company_name' => 'YS / Yeni struktur',
                'position' => 'Şöbə müdiri',
                'position_id' => $newPosition->id,
                'structure_id' => $newStructure->id,
                'join_date' => '2026-03-15',
                'leave_date' => null,
                'is_current' => true,
                'use_lookup' => true,
                'is_special_service' => false,
            ]],
        ]);

        $personnel->refresh();

        $this->assertSame($newPosition->id, $personnel->position_id);
        $this->assertSame($newStructure->id, $personnel->structure_id);

        $this->assertDatabaseHas('personnel_labor_activities', [
            'tabel_no' => $personnel->tabel_no,
            'position_id' => $newPosition->id,
            'structure_id' => $newStructure->id,
            'is_current' => 1,
        ]);
    }

    public function test_it_keeps_existing_labor_rows_when_adding_a_new_current_row(): void
    {
        $personnel = $this->makePersonnel();
        $oldStructureId = $personnel->structure_id;
        $oldPositionId = $personnel->position_id;

        $existing = $personnel->laborActivities()->create([
            'company_name' => 'Köhnə struktur',
            'structure_id' => $oldStructureId,
            'position' => 'Proqramçı',
            'position_id' => $oldPositionId,
            'join_date' => '2024-01-01',
            'leave_date' => '2026-03-14',
            'is_current' => false,
            'is_special_service' => false,
        ]);

        $newStructure = Structure::query()->create([
            'id' => 601,
            'name' => 'Yeni struktur 2',
            'shortname' => 'YS2',
            'parent_id' => null,
            'coefficient' => 1,
            'code' => 601,
            'level' => 1,
        ]);

        $newPosition = Position::query()->create([
            'id' => 601,
            'name' => 'Şöbə rəisi',
            'approval_rank' => 1,
            'is_approval_target' => true,
        ]);

        app(PersonnelRelationsService::class)->update($personnel, [
            'labor_activities' => [
                [
                    'id' => $existing->id,
                    'company_name' => 'Köhnə struktur',
                    'structure_id' => $oldStructureId,
                    'position' => 'Proqramçı',
                    'position_id' => $oldPositionId,
                    'join_date' => '2024-01-01',
                    'leave_date' => '2026-03-14',
                    'is_current' => false,
                    'is_special_service' => false,
                ],
                [
                    'company_name' => 'Yeni struktur 2',
                    'structure_id' => $newStructure->id,
                    'position' => 'Şöbə rəisi',
                    'position_id' => $newPosition->id,
                    'join_date' => '2026-03-15',
                    'leave_date' => null,
                    'is_current' => true,
                    'is_special_service' => false,
                ],
            ],
        ]);

        $this->assertSame(2, $personnel->laborActivities()->count());
        $this->assertDatabaseHas('personnel_labor_activities', [
            'id' => $existing->id,
            'tabel_no' => $personnel->tabel_no,
            'position_id' => $oldPositionId,
            'structure_id' => $oldStructureId,
        ]);
        $this->assertDatabaseHas('personnel_labor_activities', [
            'tabel_no' => $personnel->tabel_no,
            'position_id' => $newPosition->id,
            'structure_id' => $newStructure->id,
            'is_current' => 1,
        ]);
    }

    private function makePersonnel(): Personnel
    {
        $user = User::factory()->create();

        Country::query()->create([
            'id' => 1,
            'code' => 'AZ',
        ]);
        EducationDegree::query()->create([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bakalavr',
        ]);
        WorkNorm::query()->create([
            'id' => 1,
            'name_az' => 'Tam',
            'name_en' => 'Full',
            'name_ru' => 'Polniy',
        ]);
        $structure = Structure::query()->create([
            'id' => 500,
            'name' => 'Köhnə struktur',
            'shortname' => 'KS',
            'parent_id' => null,
            'coefficient' => 1,
            'code' => 500,
            'level' => 1,
        ]);
        $position = Position::query()->create([
            'id' => 500,
            'name' => 'Proqramçı',
            'approval_rank' => 0,
            'is_approval_target' => true,
        ]);

        return Personnel::withoutEvents(function () use ($user, $structure, $position) {
            return Personnel::query()->create([
                'tabel_no' => 'TB'.Str::upper(Str::random(6)),
                'surname' => 'Doe',
                'name' => 'John',
                'patronymic' => 'Smith',
                'birthdate' => '1990-01-01',
                'gender' => 1,
                'mobile' => '994501112233',
                'nationality_id' => 1,
                'pin' => 'P1234567',
                'residental_address' => 'Main st',
                'education_degree_id' => 1,
                'structure_id' => $structure->id,
                'position_id' => $position->id,
                'work_norm_id' => 1,
                'join_work_date' => '2020-01-01',
                'added_by' => $user->id,
                'is_pending' => false,
            ]);
        });
    }
}
