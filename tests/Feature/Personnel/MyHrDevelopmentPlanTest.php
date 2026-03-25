<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\TrainingCompetency;
use App\Models\TrainingLevel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\TrainingSessionParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyHrDevelopmentPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_hr_development_plan_tab_shows_personnel_training_needs_and_sessions(): void
    {
        $this->seedReferenceData();
        $refs = $this->seedTrainingNeedReferenceData();

        $user = User::factory()->create([
            'is_active' => true,
            'email' => 'employee@example.test',
        ]);
        $user->givePermissionTo(Permission::findOrCreate('show-my-hr', 'web'));

        $personnel = $this->makePersonnel($user->email);

        $need = TrainingNeedItem::query()->create([
            'personnel_id' => $personnel->id,
            'training_competency_id' => $refs['competency_id'],
            'recommended_program_id' => $refs['program_id'],
            'target_level_id' => $refs['level_id'],
            'priority' => 'high',
            'source' => 'employee_request',
            'status' => 'planned',
            'target_completion_date' => '2026-04-10',
            'reason' => 'Daha dərin təhlükəsizlik praktikasına ehtiyac var.',
            'plan_note' => 'Əvvəlcə daxili təlimlə başlamaq tövsiyə olunur.',
        ]);

        $session = TrainingSession::query()->create([
            'training_program_id' => $refs['program_id'],
            'title' => 'İnformasiya təhlükəsizliyi sessiyası',
            'scheduled_start_at' => '2026-03-28 10:00:00',
            'scheduled_end_at' => '2026-03-28 13:00:00',
            'location' => 'Konfrans zalı',
            'status' => 'scheduled',
        ]);

        TrainingSessionParticipant::query()->create([
            'training_session_id' => $session->id,
            'personnel_id' => $personnel->id,
            'training_need_item_id' => $need->id,
            'attendance_status' => 'confirmed',
        ]);

        $this->actingAs($user)
            ->get(route('my-hr', ['tab' => 'development-plan']))
            ->assertOk()
            ->assertSee('Fərdi inkişaf planım')
            ->assertSee('İnformasiya təhlükəsizliyi')
            ->assertSee('Tövsiyə olunan proqram')
            ->assertSee('Daxili təlim')
            ->assertSee('İnformasiya təhlükəsizliyi sessiyası')
            ->assertSee('Təsdiqlənib');
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

        if (! DB::table('country_translations')->where('id', 1)->exists()) {
            DB::table('country_translations')->insert([
                'id' => 1,
                'country_id' => 1,
                'locale' => 'az',
                'title' => 'Azərbaycan',
            ]);
        }

        if (! DB::table('education_degrees')->where('id', 1)->exists()) {
            DB::table('education_degrees')->insert([
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Bachelor',
            ]);
        }

        if (! DB::table('structures')->where('id', 1)->exists()) {
            DB::table('structures')->insert([
                'id' => 1,
                'name' => 'HQ',
                'shortname' => 'HQ',
                'parent_id' => null,
                'coefficient' => 1.10,
                'code' => 10,
                'level' => 1,
            ]);
        }

        if (! DB::table('positions')->where('id', 1)->exists()) {
            DB::table('positions')->insert([
                'id' => 1,
                'name' => 'Officer',
            ]);
        }

        if (! DB::table('work_norms')->where('id', 1)->exists()) {
            DB::table('work_norms')->insert([
                'id' => 1,
                'name_az' => 'Tam iş günü',
                'name_en' => 'Full time',
                'name_ru' => 'Full time',
            ]);
        }
    }

    private function seedTrainingNeedReferenceData(): array
    {
        DB::table('training_competency_groups')->insert([
            'id' => 1,
            'name' => 'Kibertəhlükəsizlik',
            'slug' => 'cyber-security',
            'description' => 'Kibertəhlükəsizlik qrupu',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $competency = TrainingCompetency::query()->create([
            'training_competency_group_id' => 1,
            'name' => 'İnformasiya təhlükəsizliyi',
            'slug' => 'information-security',
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        $program = TrainingProgram::query()->create([
            'title' => 'Daxili təlim',
            'slug' => 'internal-training',
            'code' => 'DTX-01',
            'delivery_type' => 'internal',
            'duration_hours' => 8,
            'is_active' => true,
        ]);

        $level = TrainingLevel::query()->firstOrCreate(
            ['score' => 5],
            [
                'name' => 'Ekspert',
                'sort_order' => 5,
                'is_default' => false,
            ]
        );

        return [
            'competency_id' => $competency->id,
            'program_id' => $program->id,
            'level_id' => $level->id,
        ];
    }
}
