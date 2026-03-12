<?php

namespace Tests\Feature\TrainingNeeds;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\EmployeeCompetencyProfile;
use App\Models\RoleCompetencyRequirement;
use App\Models\TrainingAnnualPlan;
use App\Models\TrainingCompetency;
use App\Models\TrainingCompetencyGroup;
use App\Models\TrainingLevel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingPlanItem;
use App\Models\TrainingProgram;
use App\Modules\TrainingNeeds\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingNeedsPlanningAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_can_generate_annual_plan_and_surface_analytics(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create(['id' => 401, 'name' => 'Instructor']);

        $personnelOne = $this->createPersonnel($user->id, 401, 'TN-401', 'Leyla');
        $personnelTwo = $this->createPersonnel($user->id, 401, 'TN-402', 'Nigar');

        $level = TrainingLevel::query()->where('score', 4)->firstOrFail();
        $group = TrainingCompetencyGroup::query()->create([
            'name' => 'Instruction',
            'slug' => 'instruction',
            'is_active' => true,
        ]);

        $competency = TrainingCompetency::query()->create([
            'training_competency_group_id' => $group->id,
            'name' => 'Assessment Design',
            'slug' => 'assessment-design',
            'is_active' => true,
        ]);

        $program = TrainingProgram::query()->create([
            'title' => 'Assessment Lab',
            'slug' => 'assessment-lab',
            'code' => 'TRN-701',
            'delivery_type' => 'internal',
            'duration_hours' => 12,
            'is_active' => true,
        ]);

        RoleCompetencyRequirement::query()->create([
            'position_id' => 401,
            'training_competency_id' => $competency->id,
            'required_level_id' => $level->id,
            'priority' => 'high',
            'is_mandatory' => true,
        ]);

        EmployeeCompetencyProfile::query()->create([
            'personnel_id' => $personnelOne->id,
            'training_competency_id' => $competency->id,
            'current_level_id' => TrainingLevel::query()->where('score', 2)->value('id'),
            'source' => 'manager_review',
            'last_assessed_at' => now(),
        ]);

        EmployeeCompetencyProfile::query()->create([
            'personnel_id' => $personnelTwo->id,
            'training_competency_id' => $competency->id,
            'current_level_id' => TrainingLevel::query()->where('score', 3)->value('id'),
            'source' => 'exam',
            'last_assessed_at' => now(),
        ]);

        TrainingNeedItem::query()->create([
            'personnel_id' => $personnelOne->id,
            'training_competency_id' => $competency->id,
            'position_id' => 401,
            'recommended_program_id' => $program->id,
            'target_level_id' => $level->id,
            'priority' => 'high',
            'source' => 'performance_gap',
            'status' => 'approved',
            'target_completion_date' => '2026-05-01',
            'reason' => 'Gap 1',
        ]);

        TrainingNeedItem::query()->create([
            'personnel_id' => $personnelTwo->id,
            'training_competency_id' => $competency->id,
            'position_id' => 401,
            'recommended_program_id' => $program->id,
            'target_level_id' => $level->id,
            'priority' => 'high',
            'source' => 'skill_gap',
            'status' => 'planned',
            'target_completion_date' => '2026-06-15',
            'reason' => 'Gap 2',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class)
            ->set('activeTab', 'planning')
            ->set('planForm.title', '2026 Təlim planı')
            ->set('planForm.plan_year', 2026)
            ->set('planForm.status', 'approved')
            ->set('planForm.auto_generate', true)
            ->call('storePlan')
            ->assertHasNoErrors();

        $planId = TrainingAnnualPlan::query()->where('title', '2026 Təlim planı')->value('id');

        $this->assertDatabaseHas('training_annual_plans', [
            'id' => $planId,
            'plan_year' => 2026,
            'covered_need_count' => 2,
            'planned_participants' => 2,
        ]);

        $this->assertDatabaseHas('training_plan_items', [
            'training_annual_plan_id' => $planId,
            'training_competency_id' => $competency->id,
            'training_program_id' => $program->id,
            'participant_count' => 2,
            'need_count' => 2,
        ]);

        $summary = $component->instance()->analyticsSummary;
        $suggestions = $component->instance()->suggestedPlanGroups;

        $this->assertSame(2, $summary['total_needs']);
        $this->assertSame(2, $summary['approved_needs']);
        $this->assertSame(2, $summary['mapped_needs']);
        $this->assertSame(2, $summary['planned_needs']);
        $this->assertSame(100.0, $summary['coverage_ratio']);
        $this->assertSame(100.0, $summary['mapping_ratio']);
        $this->assertCount(1, $suggestions);
        $this->assertSame('Assessment Lab', $suggestions->first()['training_program_title']);
        $this->assertSame(2, $suggestions->first()['participant_count']);
        $this->assertContains('mandatory', $suggestions->first()['suggested_reasons']);
        $this->assertGreaterThan(0, $suggestions->first()['suggested_score']);

        $this->assertSame(1, TrainingAnnualPlan::query()->count());
        $this->assertSame(1, TrainingPlanItem::query()->count());
    }

    public function test_generated_plan_items_can_move_through_hr_review_flow(): void
    {
        $user = \App\Models\User::factory()->create(['name' => 'HR Reviewer']);
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create(['id' => 402, 'name' => 'Specialist']);

        $personnel = $this->createPersonnel($user->id, 402, 'TN-403', 'Aylin');
        $level = TrainingLevel::query()->where('score', 4)->firstOrFail();
        $currentLevel = TrainingLevel::query()->where('score', 2)->firstOrFail();
        $group = TrainingCompetencyGroup::query()->create([
            'name' => 'Operations',
            'slug' => 'operations',
            'is_active' => true,
        ]);

        $competency = TrainingCompetency::query()->create([
            'training_competency_group_id' => $group->id,
            'name' => 'Documentation Discipline',
            'slug' => 'documentation-discipline',
            'is_active' => true,
        ]);

        $program = TrainingProgram::query()->create([
            'title' => 'Documentation Bootcamp',
            'slug' => 'documentation-bootcamp',
            'delivery_type' => 'internal',
            'duration_hours' => 8,
            'is_active' => true,
        ]);

        RoleCompetencyRequirement::query()->create([
            'position_id' => 402,
            'training_competency_id' => $competency->id,
            'required_level_id' => $level->id,
            'priority' => 'high',
            'is_mandatory' => true,
        ]);

        EmployeeCompetencyProfile::query()->create([
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'current_level_id' => $currentLevel->id,
            'source' => 'exam',
            'last_assessed_at' => now(),
        ]);

        TrainingNeedItem::query()->create([
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'position_id' => 402,
            'recommended_program_id' => $program->id,
            'target_level_id' => $level->id,
            'priority' => 'high',
            'source' => 'skill_gap',
            'status' => 'approved',
            'target_completion_date' => '2026-08-10',
            'reason' => 'Review flow gap',
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'planning')
            ->set('planForm.title', '2026 Review planı')
            ->set('planForm.plan_year', 2026)
            ->set('planForm.auto_generate', true)
            ->call('storePlan')
            ->assertHasNoErrors();

        $plan = TrainingAnnualPlan::query()->where('title', '2026 Review planı')->firstOrFail();
        $item = TrainingPlanItem::query()->where('training_annual_plan_id', $plan->id)->firstOrFail();

        $this->assertSame('suggested', $item->review_status);
        $this->assertSame('review', $plan->fresh()->status);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'planning')
            ->call('selectPlanItemForReview', $item->id)
            ->set('planItemReviewForm.participant_count', 3)
            ->set('planItemReviewForm.estimated_budget', 450)
            ->set('planItemReviewForm.priority', 'high')
            ->set('planItemReviewForm.review_note', 'HR adjusted by reviewer')
            ->call('savePlanItemReview', 'hr_adjusted')
            ->assertHasNoErrors();

        $item->refresh();
        $this->assertSame('hr_adjusted', $item->review_status);
        $this->assertSame('HR adjusted by reviewer', $item->review_note);
        $this->assertSame(3, $item->participant_count);
        $this->assertSame('review', $plan->fresh()->status);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'planning')
            ->call('selectPlanItemForReview', $item->id)
            ->set('planItemReviewForm.participant_count', 3)
            ->set('planItemReviewForm.estimated_budget', 450)
            ->set('planItemReviewForm.priority', 'high')
            ->set('planItemReviewForm.review_note', 'Approved by HR')
            ->call('savePlanItemReview', 'approved')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_plan_items', [
            'id' => $item->id,
            'review_status' => 'approved',
            'reviewed_by' => $user->id,
        ]);
        $this->assertSame('approved', $plan->fresh()->status);
    }

    private function createPersonnel(int $userId, int $positionId, string $tabelNo, string $name): Personnel
    {
        if (! DB::table('countries')->where('id', 1)->exists()) {
            DB::table('countries')->insert([
                'id' => 1,
                'code' => 'AZ',
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
                'name' => 'DMX',
                'shortname' => 'DMX',
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

        return Personnel::query()->create([
            'tabel_no' => $tabelNo,
            'surname' => 'Quliyeva',
            'name' => $name,
            'patronymic' => 'Adil',
            'birthdate' => '1991-01-01',
            'gender' => 2,
            'phone' => '0120000000',
            'mobile' => '0500000000',
            'email' => strtolower($name).'@example.test',
            'nationality_id' => 1,
            'pin' => strtoupper(substr($name, 0, 3)).'1234',
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
