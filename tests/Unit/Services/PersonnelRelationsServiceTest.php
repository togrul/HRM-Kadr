<?php

namespace Tests\Unit\Services;

use App\Enums\KnowledgeStatusEnum;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Language;
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

    public function test_it_creates_multi_relations_from_payload(): void
    {
        $personnel = $this->makePersonnel();
        $language = Language::create(['id' => 1, 'name' => 'English']);

        $payloads = [
            'service_cards' => [
                [
                    'card_number' => 'CARD-1',
                    'given_date' => '2025-01-01',
                    'valid_date' => '2030-01-01',
                ],
            ],
            'languages' => [
                [
                    'language_id' => $language->id,
                    'knowledge_status' => KnowledgeStatusEnum::Good->value,
                ],
            ],
        ];

        app(PersonnelRelationsService::class)->create($personnel, $payloads, []);

        $this->assertDatabaseHas('personnel_cards', [
            'tabel_no' => $personnel->tabel_no,
            'card_number' => 'CARD-1',
        ]);

        $this->assertDatabaseHas('personnel_foreign_languages', [
            'tabel_no' => $personnel->tabel_no,
            'language_id' => $language->id,
            'knowledge_status' => KnowledgeStatusEnum::Good->value,
        ]);
    }

    public function test_it_reconciles_relations_during_update(): void
    {
        $personnel = $this->makePersonnel();
        $personnel->cards()->create([
            'card_number' => 'OLD',
            'given_date' => '2022-01-01',
            'valid_date' => '2024-01-01',
        ]);

        $payloads = [
            'service_cards' => [
                [
                    'card_number' => 'NEW',
                    'given_date' => '2024-02-01',
                    'valid_date' => '2026-02-01',
                ],
            ],
        ];

        app(PersonnelRelationsService::class)->update($personnel, $payloads);

        $this->assertDatabaseHas('personnel_cards', [
            'tabel_no' => $personnel->tabel_no,
            'card_number' => 'NEW',
        ]);

        $this->assertDatabaseMissing('personnel_cards', [
            'tabel_no' => $personnel->tabel_no,
            'card_number' => 'OLD',
        ]);
    }

    private function makePersonnel(): Personnel
    {
        $user = User::factory()->create();

        $country = Country::create(['id' => 1, 'code' => 'AZ']);
        $degree = EducationDegree::create([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bakalavr',
        ]);

        $structure = Structure::create([
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1,
            'code' => 1,
            'level' => 1,
        ]);

        $position = Position::create([
            'id' => 1,
            'name' => 'Officer',
        ]);

        $workNorm = WorkNorm::create([
            'id' => 1,
            'name_az' => 'Full',
            'name_en' => 'Full',
            'name_ru' => 'Full',
        ]);

        return Personnel::withoutEvents(function () use ($user, $country, $degree, $structure, $position, $workNorm) {
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
            'structure_id' => $structure->id,
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
