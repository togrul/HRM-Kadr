<?php

namespace Tests\Feature\TrainingNeeds;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\TrainingCompetency;
use App\Models\TrainingCompetencyGroup;
use App\Models\TrainingLevel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingProgram;
use App\Modules\TrainingNeeds\Livewire\Dashboard;
use App\Modules\TrainingNeeds\Livewire\Lists as TrainingNeedsLists;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingNeedsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_training_needs_route(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);

        $this->actingAs($user)
            ->get(route('training-needs'))
            ->assertOk()
            ->assertSee(__('training_needs::dashboard.title'));
    }

    public function test_training_needs_route_requires_view_permission(): void
    {
        $user = \App\Models\User::factory()->create();
        Permission::findOrCreate('show-training-needs', 'web');

        $this->actingAs($user)
            ->get(route('training-needs'))
            ->assertForbidden();
    }

    public function test_dashboard_can_create_foundation_catalog_records(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        $level = TrainingLevel::query()->where('score', 3)->firstOrFail();
        $advancedLevel = TrainingLevel::query()->where('score', 4)->firstOrFail();

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 101,
            'name' => 'Analyst',
        ]);

        $personnel = $this->createPersonnel($user->id, 101);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('groupForm.name', 'Leadership')
            ->set('groupForm.description', 'Leadership competencies')
            ->call('storeGroup')
            ->assertHasNoErrors()
            ->set('competencyForm.training_competency_group_id', TrainingCompetencyGroup::query()->where('slug', 'leadership')->value('id'))
            ->set('competencyForm.name', 'Decision Making')
            ->set('competencyForm.description', 'Makes sound and timely decisions')
            ->set('competencyForm.is_mandatory', true)
            ->call('storeCompetency')
            ->assertHasNoErrors()
            ->set('programForm.title', 'Leadership Basics')
            ->set('programForm.code', 'TRN-101')
            ->set('programForm.delivery_type', 'internal')
            ->set('programForm.duration_hours', 8)
            ->call('storeProgram')
            ->assertHasNoErrors()
            ->set('programMapForm.training_program_id', TrainingProgram::query()->where('slug', 'leadership-basics')->value('id'))
            ->set('programMapForm.training_competency_id', TrainingCompetency::query()->where('slug', 'decision-making')->value('id'))
            ->set('programMapForm.target_level_id', $level->id)
            ->call('storeProgramMap')
            ->assertHasNoErrors()
            ->set('requirementForm.position_id', 101)
            ->set('requirementForm.training_competency_id', TrainingCompetency::query()->where('slug', 'decision-making')->value('id'))
            ->set('requirementForm.required_level_id', $level->id)
            ->set('requirementForm.priority', 'high')
            ->set('requirementForm.is_mandatory', true)
            ->call('storeRequirement')
            ->assertHasNoErrors()
            ->set('profileForm.personnel_id', $personnel->id)
            ->set('profileForm.training_competency_id', TrainingCompetency::query()->where('slug', 'decision-making')->value('id'))
            ->set('profileForm.current_level_id', $level->id)
            ->set('profileForm.source', 'manager_review')
            ->set('profileForm.last_assessed_at', '2026-03-10')
            ->call('storeProfile')
            ->assertHasNoErrors()
            ->set('needForm.personnel_id', $personnel->id)
            ->set('needForm.training_competency_id', TrainingCompetency::query()->where('slug', 'decision-making')->value('id'))
            ->set('needForm.recommended_program_id', TrainingProgram::query()->where('slug', 'leadership-basics')->value('id'))
            ->set('needForm.target_level_id', $advancedLevel->id)
            ->set('needForm.priority', 'high')
            ->set('needForm.source', 'manager_request')
            ->set('needForm.status', 'planned')
            ->set('needForm.reason', 'Promotion readiness gap')
            ->set('needForm.plan_note', 'Enroll in next internal cohort')
            ->set('needForm.target_completion_date', '2026-06-01')
            ->call('storeNeed')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_competency_groups', [
            'name' => 'Leadership',
            'slug' => 'leadership',
        ]);

        $this->assertDatabaseHas('training_competencies', [
            'name' => 'Decision Making',
            'slug' => 'decision-making',
            'is_mandatory' => 1,
        ]);

        $this->assertDatabaseHas('training_programs', [
            'title' => 'Leadership Basics',
            'slug' => 'leadership-basics',
            'code' => 'TRN-101',
        ]);

        $this->assertDatabaseHas('training_program_competency_map', [
            'training_program_id' => TrainingProgram::query()->where('slug', 'leadership-basics')->value('id'),
            'training_competency_id' => TrainingCompetency::query()->where('slug', 'decision-making')->value('id'),
            'target_level_id' => $level->id,
        ]);

        $this->assertDatabaseHas('role_competency_requirements', [
            'position_id' => 101,
            'training_competency_id' => TrainingCompetency::query()->where('slug', 'decision-making')->value('id'),
            'required_level_id' => $level->id,
            'priority' => 'high',
            'is_mandatory' => 1,
        ]);

        $this->assertDatabaseHas('employee_competency_profiles', [
            'personnel_id' => $personnel->id,
            'training_competency_id' => TrainingCompetency::query()->where('slug', 'decision-making')->value('id'),
            'current_level_id' => $level->id,
            'source' => 'manager_review',
        ]);

        $this->assertDatabaseHas('training_need_items', [
            'personnel_id' => $personnel->id,
            'training_competency_id' => TrainingCompetency::query()->where('slug', 'decision-making')->value('id'),
            'recommended_program_id' => TrainingProgram::query()->where('slug', 'leadership-basics')->value('id'),
            'target_level_id' => $advancedLevel->id,
            'status' => 'planned',
            'source' => 'manager_request',
        ]);

        $this->assertSame('Enroll in next internal cohort', TrainingNeedItem::query()->latest('id')->value('plan_note'));
    }

    public function test_lists_component_renders_translated_search_label_and_presented_reason(): void
    {
        app()->setLocale('az');

        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 102,
            'name' => 'Trainer',
        ]);

        $personnel = $this->createPersonnel($user->id, 102);

        $group = TrainingCompetencyGroup::query()->create([
            'name' => 'Communication',
            'slug' => 'communication',
            'is_active' => true,
        ]);

        $competency = TrainingCompetency::query()->create([
            'training_competency_group_id' => $group->id,
            'name' => 'Presentation Skills',
            'slug' => 'presentation-skills',
            'is_active' => true,
        ]);

        $program = TrainingProgram::query()->create([
            'title' => 'Presentation Lab',
            'slug' => 'presentation-lab',
            'delivery_type' => 'internal',
            'duration_hours' => 6,
            'is_active' => true,
        ]);

        $need = TrainingNeedItem::query()->create([
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'position_id' => 102,
            'recommended_program_id' => $program->id,
            'target_level_id' => TrainingLevel::query()->where('score', 4)->value('id'),
            'priority' => 'high',
            'source' => 'performance_gap',
            'status' => 'approved',
            'target_completion_date' => '2026-05-01',
            'reason' => 'Low performance score detected on form #1, item #2: 45.00',
        ]);

        $this->actingAs($user);

        Livewire::test(TrainingNeedsLists::class)
            ->set('selectedRowId', $need->id)
            ->assertSee(__('training_needs::dashboard.fields.search'))
            ->assertSee(__('performance_evaluation::dashboard.messages.performance_gap_reason', [
                'form' => 1,
                'item' => 2,
                'score' => '45.00',
            ]));
    }

    private function createPersonnel(int $userId, int $positionId): Personnel
    {
        DB::table('countries')->insert([
            'id' => 1,
            'code' => 'AZ',
        ]);

        DB::table('education_degrees')->insert([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);

        DB::table('structures')->insert([
            'id' => 1,
            'name' => 'DMX',
            'shortname' => 'DMX',
        ]);

        DB::table('work_norms')->insert([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);

        return Personnel::query()->create([
            'tabel_no' => 'TN-001',
            'surname' => 'Aliyev',
            'name' => 'Murad',
            'patronymic' => 'Rashad',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'phone' => '0120000000',
            'mobile' => '0500000000',
            'email' => 'murad@example.test',
            'nationality_id' => 1,
            'pin' => 'ABC1234',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => $positionId,
            'work_norm_id' => 1,
            'join_work_date' => '2024-01-01',
            'added_by' => $userId,
        ]);
    }

    private function grantTrainingNeedsPermissions(\App\Models\User $user): void
    {
        foreach ([
            'show-training-needs',
            'manage-training-needs',
            'review-training-needs',
            'export-training-needs',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo([
            'show-training-needs',
            'manage-training-needs',
            'review-training-needs',
            'export-training-needs',
        ]);
    }
}
