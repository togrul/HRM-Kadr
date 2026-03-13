<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Candidate;
use App\Models\Personnel;
use App\Models\User;
use App\Services\ImportCandidateToPersonnel;
use App\Services\PersonnelTabelNoGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportCandidateToPersonnelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_throws_friendly_message_when_candidate_personnel_already_exists(): void
    {
        app()->setLocale('az');

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');
        $user = User::factory()->create();

        DB::table('countries')->insert([
            'id' => 11,
            'code' => 'AZ',
        ]);

        DB::table('education_degrees')->insert([
            'id' => 100,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Бакалавр',
        ]);

        DB::table('positions')->insert([
            'id' => 1010,
            'name' => 'Mütəxəssis',
        ]);

        $structureId = DB::table('structures')->insertGetId([
            'name' => '10-cu idarə',
            'shortname' => '10-cu id',
        ]);

        DB::table('work_norms')->insert([
            'id' => 10,
            'name_az' => 'Tam iş vaxtı',
            'name_en' => 'Full time',
            'name_ru' => 'Полный день',
        ]);

        $candidate = Candidate::query()->create([
            'name' => 'Elmira',
            'surname' => 'İbrahimova',
            'patronymic' => 'Cavid',
            'structure_id' => $structureId,
            'height' => 170,
            'military_service' => 'yoxdur',
            'status_id' => 30,
            'phone' => '+994501112233',
            'birthdate' => '1990-01-01',
            'gender' => 2,
            'knowledge_test' => 5,
            'physical_fitness_exam' => 5,
            'research_date' => '2026-03-13',
            'research_result' => 'müsbət',
            'discrediting_information' => 'yoxdur',
            'examination_date' => '2026-03-13',
            'appeal_date' => '2026-03-13',
            'application_date' => '2026-03-13',
            'requisition_date' => '2026-03-13',
            'initial_documents' => 'tamdir',
            'documents_completeness' => 'tamdir',
            'attitude_to_military' => 'h/m',
            'characteristics' => 'musbet',
            'hhk_date' => '2026-03-13',
            'hhk_result' => 'yararli',
            'presented_by' => 'TI',
            'creator_id' => $user->id,
        ]);

        Personnel::query()->create([
            'tabel_no' => 'NMZD'.$candidate->id,
            'surname' => 'İbrahimova',
            'name' => 'Elmira',
            'patronymic' => 'Cavid',
            'birthdate' => '1990-01-01',
            'gender' => 2,
            'phone' => '+994501112233',
            'mobile' => '1234567',
            'email' => 'existing@example.com',
            'nationality_id' => 11,
            'pin' => '1234567',
            'residental_address' => 'ünvan',
            'education_degree_id' => 100,
            'structure_id' => $structureId,
            'position_id' => 1010,
            'join_work_date' => '2026-03-13',
            'added_by' => $user->id,
            'work_norm_id' => 10,
            'is_pending' => true,
        ]);

        $service = new ImportCandidateToPersonnel(app(PersonnelTabelNoGeneratorService::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('İbrahimova Elmira Cavid artıq personal kimi mövcuddur. Tabel nömrəsi: NMZD'.$candidate->id.'.');

        $service->handle([
            [
                'personnel_id' => $candidate->id,
                'structure_id' => $structureId,
                'position_id' => 1010,
                'component_id' => 1,
            ],
        ], OrderStatusEnum::PENDING->value);
    }
}
