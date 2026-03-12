<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\EmployeeCompetencyProfile;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestBank;
use App\Models\PerformanceTestQuestion;
use App\Models\PerformanceTestQuestionOption;
use App\Models\PerformanceTestSession;
use App\Models\PerformanceTestTrainingNeedLink;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\RoleCompetencyRequirement;
use App\Models\TrainingCompetency;
use App\Models\TrainingCompetencyGroup;
use App\Models\TrainingLevel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingProgram;
use App\Models\TrainingProgramCompetency;
use App\Modules\PerformanceEvaluation\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PerformanceEvaluationSkillMeasurementTest extends TestCase
{
    use RefreshDatabase;

    public function test_skill_measurement_flow_scores_attempts_reviews_open_answers_and_creates_training_needs(): void
    {
        $user = \App\Models\User::factory()->create(['name' => 'HR Specialist']);
        $reviewer = \App\Models\User::factory()->create(['name' => 'Skill Reviewer']);
        $this->grantPerformancePermissions($user);

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 302,
            'name' => 'Assistant Lecturer',
        ]);

        $personnel = $this->createPersonnel($user->id, 302);
        $competency = $this->createCompetency();
        $requiredLevel = TrainingLevel::query()->where('score', 4)->firstOrFail();
        $this->createTrainingProgram($competency->id, $requiredLevel->id);

        RoleCompetencyRequirement::query()->create([
            'position_id' => 302,
            'training_competency_id' => $competency->id,
            'required_level_id' => $requiredLevel->id,
            'priority' => 'high',
            'is_mandatory' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'tests')
            ->set('bankForm.name', 'Pedagogy Skill Test')
            ->set('bankForm.code', 'SKILL-101')
            ->set('bankForm.pass_score', 70)
            ->set('bankForm.duration_minutes', 45)
            ->set('bankForm.max_attempts', 2)
            ->call('storeTestBank')
            ->assertHasNoErrors();

        $bankId = PerformanceTestBank::query()->where('code', 'SKILL-101')->value('id');

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'tests')
            ->set('questionForm.performance_test_bank_id', $bankId)
            ->set('questionForm.training_competency_id', $competency->id)
            ->set('questionForm.question_type', 'multiple_choice')
            ->set('questionForm.prompt', 'What is the best indicator of structured classroom planning?')
            ->set('questionForm.max_score', 100)
            ->set('questionForm.options_text', "Lesson plan alignment | 1 | 100\nLate attendance | 0 | 0")
            ->call('storeTestQuestion')
            ->assertHasNoErrors()
            ->set('questionForm.performance_test_bank_id', $bankId)
            ->set('questionForm.training_competency_id', $competency->id)
            ->set('questionForm.question_type', 'open_answer')
            ->set('questionForm.prompt', 'Explain how you adapt teaching methods for mixed-level groups.')
            ->set('questionForm.max_score', 100)
            ->call('storeTestQuestion')
            ->assertHasNoErrors()
            ->set('sessionForm.performance_test_bank_id', $bankId)
            ->set('sessionForm.personnel_id', $personnel->id)
            ->set('sessionForm.reviewer_id', $reviewer->id)
            ->set('sessionForm.status', 'assigned')
            ->call('storeTestSession')
            ->assertHasNoErrors();

        $sessionId = PerformanceTestSession::query()->where('performance_test_bank_id', $bankId)->value('id');
        $mcQuestionId = PerformanceTestQuestion::query()->where('question_type', 'multiple_choice')->value('id');
        $openQuestionId = PerformanceTestQuestion::query()->where('question_type', 'open_answer')->value('id');
        $correctOptionId = PerformanceTestQuestionOption::query()->where('performance_test_question_id', $mcQuestionId)->where('is_correct', true)->value('id');

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'tests')
            ->set('attemptAnswerForm.performance_test_session_id', $sessionId)
            ->set('attemptAnswerForm.performance_test_question_id', $mcQuestionId)
            ->set('attemptAnswerForm.attempt_no', 1)
            ->set('attemptAnswerForm.selected_option_id', $correctOptionId)
            ->call('storeAttemptAnswer')
            ->assertHasNoErrors()
            ->set('attemptAnswerForm.performance_test_session_id', $sessionId)
            ->set('attemptAnswerForm.performance_test_question_id', $openQuestionId)
            ->set('attemptAnswerForm.attempt_no', 1)
            ->set('attemptAnswerForm.answer_text', 'I adjust pace and materials according to individual gaps.')
            ->call('storeAttemptAnswer')
            ->assertHasNoErrors();

        $attemptId = PerformanceTestAttempt::query()->where('performance_test_session_id', $sessionId)->value('id');

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'tests')
            ->set('attemptSubmitForm.performance_test_attempt_id', $attemptId)
            ->call('finalizeAttempt')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('performance_test_attempts', [
            'id' => $attemptId,
            'status' => 'review_pending',
        ]);

        $mcAnswer = PerformanceTestAttemptAnswer::query()->where('performance_test_attempt_id', $attemptId)->where('performance_test_question_id', $mcQuestionId)->firstOrFail();
        $openAnswer = PerformanceTestAttemptAnswer::query()->where('performance_test_attempt_id', $attemptId)->where('performance_test_question_id', $openQuestionId)->firstOrFail();

        $this->assertSame('auto_scored', $mcAnswer->review_status);
        $this->assertSame('pending', $openAnswer->review_status);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'tests')
            ->set('reviewForm.performance_test_attempt_answer_id', $openAnswer->id)
            ->set('reviewForm.score', 0)
            ->set('reviewForm.feedback', 'Depth is insufficient.')
            ->call('reviewAttemptAnswer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('performance_test_attempts', [
            'id' => $attemptId,
            'status' => 'completed',
            'percentage' => 50.00,
            'passed' => false,
        ]);

        $this->assertDatabaseHas('employee_competency_profiles', [
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'source' => 'skill_measurement',
        ]);

        $this->assertDatabaseHas('training_need_items', [
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'source' => 'skill_gap',
        ]);

        $this->assertDatabaseHas('performance_test_training_need_links', [
            'performance_test_attempt_id' => $attemptId,
            'training_competency_id' => $competency->id,
        ]);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'tests')
            ->set('reviewForm.performance_test_attempt_answer_id', $openAnswer->id)
            ->set('reviewForm.score', 100)
            ->set('reviewForm.feedback', 'Strong updated answer.')
            ->call('reviewAttemptAnswer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('performance_test_attempts', [
            'id' => $attemptId,
            'percentage' => 100.00,
            'passed' => true,
        ]);

        $this->assertSame(0, TrainingNeedItem::query()->where('source', 'skill_gap')->count());
        $this->assertSame(0, PerformanceTestTrainingNeedLink::query()->count());
        $this->assertNotNull(EmployeeCompetencyProfile::query()->where('personnel_id', $personnel->id)->where('training_competency_id', $competency->id)->value('current_level_id'));
    }

    private function createCompetency(): TrainingCompetency
    {
        $group = TrainingCompetencyGroup::query()->create([
            'name' => 'Teaching Skills',
            'slug' => 'teaching-skills',
            'is_active' => true,
        ]);

        return TrainingCompetency::query()->create([
            'training_competency_group_id' => $group->id,
            'name' => 'Adaptive Teaching',
            'slug' => 'adaptive-teaching',
            'is_active' => true,
        ]);
    }

    private function createTrainingProgram(int $competencyId, int $targetLevelId): TrainingProgram
    {
        $program = TrainingProgram::query()->create([
            'title' => 'Adaptive Teaching Workshop',
            'slug' => 'adaptive-teaching-workshop',
            'code' => 'TRN-501',
            'delivery_type' => 'internal',
            'is_active' => true,
        ]);

        TrainingProgramCompetency::query()->create([
            'training_program_id' => $program->id,
            'training_competency_id' => $competencyId,
            'target_level_id' => $targetLevelId,
        ]);

        return $program;
    }

    private function grantPerformancePermissions(\App\Models\User $user): void
    {
        foreach ([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
        ]);
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
            'tabel_no' => 'PE-TEST-001',
            'surname' => 'Mammadov',
            'name' => 'Orxan',
            'patronymic' => 'Rauf',
            'birthdate' => '1992-02-10',
            'gender' => 1,
            'phone' => '0121111111',
            'mobile' => '0501111111',
            'email' => 'orxan@example.test',
            'nationality_id' => 1,
            'pin' => 'AAA1111',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => $positionId,
            'work_norm_id' => 1,
            'join_work_date' => '2024-01-01',
            'added_by' => $userId,
        ]);
    }
}
