<?php

namespace Tests\Feature\TrainingNeeds;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\TrainingAnnualPlan;
use App\Models\TrainingCompetency;
use App\Models\TrainingCompetencyGroup;
use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingLevel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Modules\TrainingNeeds\Livewire\Dashboard;
use App\Modules\TrainingNeeds\Livewire\Analytics as TrainingNeedsAnalytics;
use App\Modules\TrainingNeeds\Livewire\Lists as TrainingNeedsLists;
use App\Modules\TrainingNeeds\Livewire\Overview as TrainingNeedsOverview;
use App\Modules\TrainingNeeds\Livewire\ResultsSummary as TrainingNeedsResultsSummary;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_overview_component_renders_training_summary_cards(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);

        $this->actingAs($user);

        Livewire::test(TrainingNeedsOverview::class)
            ->assertSee(__('training_needs::dashboard.cards.foundation_scope'))
            ->assertSee(__('training_needs::dashboard.cards.recent_competencies'))
            ->assertSee(__('training_needs::dashboard.cards.coverage_snapshot'));
    }

    public function test_analytics_component_renders_reporting_cards(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);

        $this->actingAs($user);

        Livewire::test(TrainingNeedsAnalytics::class)
            ->assertSee(__('training_needs::dashboard.cards.coverage_ratio'))
            ->assertSee(__('training_needs::dashboard.cards.reporting_summary'))
            ->assertSee(__('training_needs::dashboard.cards.top_gap_positions'));
    }

    public function test_results_summary_component_renders_feedback_and_export_cards(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);

        $this->actingAs($user);

        Livewire::test(TrainingNeedsResultsSummary::class)
            ->assertSee(__('training_needs::dashboard.cards.recent_feedback_forms'))
            ->assertSee(__('training_needs::dashboard.cards.feedback_session_summary'))
            ->assertSee(__('training_needs::dashboard.cards.export_reports'));
    }

    public function test_dashboard_refreshes_results_summary_after_feedback_and_delivery_mutations(): void
    {
        Storage::fake('public');

        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 104,
            'name' => 'Coordinator',
        ]);

        $personnel = $this->createPersonnel($user->id, 104);
        $program = TrainingProgram::query()->create([
            'title' => 'Coordination Lab',
            'slug' => 'coordination-lab',
            'delivery_type' => 'internal',
            'duration_hours' => 4,
            'is_active' => true,
        ]);

        $session = TrainingSession::query()->create([
            'training_program_id' => $program->id,
            'title' => 'April Session',
            'scheduled_start_at' => '2026-04-01 09:00:00',
            'scheduled_end_at' => '2026-04-01 13:00:00',
            'status' => 'planned',
        ]);

        $record = TrainingDeliveryRecord::query()->create([
            'training_session_id' => $session->id,
            'training_program_id' => $program->id,
            'personnel_id' => $personnel->id,
            'result_status' => 'completed',
            'completed_at' => '2026-04-01 14:00:00',
            'certificate_path' => 'training-certificates/test-certificate.pdf',
            'certificate_name' => 'test-certificate.pdf',
        ]);

        Storage::disk('public')->put('training-certificates/test-certificate.pdf', 'certificate');

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('resultsSummaryVersion', 0)
            ->set('feedbackForm.training_session_id', $session->id)
            ->set('feedbackForm.title', 'Session Feedback')
            ->set('feedbackForm.status', 'open')
            ->set('feedbackForm.default_question_type', 'rating')
            ->set('feedbackForm.questions_text', 'How useful was it?')
            ->call('storeFeedbackForm')
            ->assertHasNoErrors()
            ->assertSet('resultsSummaryVersion', 1)
            ->call('deleteDeliveryCertificate', $record->id)
            ->assertHasNoErrors()
            ->assertSet('resultsSummaryVersion', 2);
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

    public function test_delivery_certificate_delete_is_confirmed_via_modal_before_execution(): void
    {
        Storage::fake('public');

        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 103,
            'name' => 'Coordinator',
        ]);

        $personnel = $this->createPersonnel($user->id, 103);
        $program = TrainingProgram::query()->create([
            'title' => 'Onboarding Lab',
            'slug' => 'onboarding-lab',
            'delivery_type' => 'internal',
            'duration_hours' => 4,
            'is_active' => true,
        ]);

        $session = TrainingSession::query()->create([
            'title' => 'March Session',
            'training_program_id' => $program->id,
            'scheduled_start_at' => '2026-03-15 09:00:00',
            'scheduled_end_at' => '2026-03-15 13:00:00',
            'status' => 'planned',
        ]);

        Storage::disk('public')->put('training-certificates/test-certificate.pdf', 'certificate');

        $record = TrainingDeliveryRecord::query()->create([
            'training_session_id' => $session->id,
            'training_program_id' => $program->id,
            'personnel_id' => $personnel->id,
            'result_status' => 'completed',
            'completed_at' => '2026-03-15 14:00:00',
            'certificate_path' => 'training-certificates/test-certificate.pdf',
            'certificate_name' => 'test-certificate.pdf',
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->call('confirmDeleteDeliveryCertificate', $record->id)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSee(__('training_needs::dashboard.confirmations.delete_certificate'))
            ->assertSee('test-certificate.pdf')
            ->call('runConfirmedDeletion')
            ->assertSet('showDeleteConfirmation', false);

        $this->assertDatabaseHas('training_delivery_records', [
            'id' => $record->id,
            'certificate_path' => null,
            'certificate_name' => null,
        ]);
    }

    public function test_dashboard_can_edit_plan_session_and_feedback_form(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);

        $program = TrainingProgram::query()->create([
            'title' => 'Communication Lab',
            'slug' => 'communication-lab',
            'delivery_type' => 'internal',
            'duration_hours' => 6,
            'is_active' => true,
        ]);

        $plan = TrainingAnnualPlan::query()->create([
            'title' => '2026 Plan',
            'plan_year' => 2026,
            'status' => 'draft',
            'auto_generated' => true,
        ]);

        $session = TrainingSession::query()->create([
            'training_annual_plan_id' => $plan->id,
            'training_program_id' => $program->id,
            'title' => 'Initial Session',
            'scheduled_start_at' => '2026-04-10 09:00:00',
            'scheduled_end_at' => '2026-04-10 12:00:00',
            'status' => 'scheduled',
            'auto_fill_participants' => false,
        ]);

        $feedbackForm = TrainingFeedbackForm::query()->create([
            'training_session_id' => $session->id,
            'title' => 'Initial Feedback',
            'status' => 'draft',
            'questions' => [
                ['type' => 'rating', 'text' => 'How useful was it?'],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->call('editPlan', $plan->id)
            ->assertSet('editingPlanId', $plan->id)
            ->set('planForm.title', '2026 Revised Plan')
            ->set('planForm.notes', 'Updated by HR')
            ->set('planForm.auto_generate', false)
            ->call('storePlan')
            ->assertHasNoErrors()
            ->assertSet('editingPlanId', null)
            ->call('editSession', $session->id)
            ->assertSet('editingSessionId', $session->id)
            ->set('sessionForm.title', 'Updated Session')
            ->set('sessionForm.location', 'Room 301')
            ->set('sessionForm.auto_fill_participants', false)
            ->call('storeSession')
            ->assertHasNoErrors()
            ->assertSet('editingSessionId', null)
            ->call('editFeedbackForm', $feedbackForm->id)
            ->assertSet('editingFeedbackFormId', $feedbackForm->id)
            ->set('feedbackForm.title', 'Updated Feedback')
            ->set('feedbackForm.questions_text', "Question 1\nQuestion 2")
            ->call('storeFeedbackForm')
            ->assertHasNoErrors()
            ->assertSet('editingFeedbackFormId', null);

        $this->assertDatabaseHas('training_annual_plans', [
            'id' => $plan->id,
            'title' => '2026 Revised Plan',
            'notes' => 'Updated by HR',
        ]);

        $this->assertDatabaseHas('training_sessions', [
            'id' => $session->id,
            'title' => 'Updated Session',
            'location' => 'Room 301',
        ]);

        $this->assertDatabaseHas('training_feedback_forms', [
            'id' => $feedbackForm->id,
            'title' => 'Updated Feedback',
            'status' => 'draft',
        ]);

        $this->assertSame(
            ['Question 1', 'Question 2'],
            collect(TrainingFeedbackForm::query()->findOrFail($feedbackForm->id)->questions)->pluck('text')->all()
        );
    }

    public function test_plan_session_and_feedback_form_deletions_are_confirmed_via_modal(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);

        $program = TrainingProgram::query()->create([
            'title' => 'Leadership Sprint',
            'slug' => 'leadership-sprint',
            'delivery_type' => 'internal',
            'duration_hours' => 8,
            'is_active' => true,
        ]);

        $plan = TrainingAnnualPlan::query()->create([
            'title' => 'Delete Me Plan',
            'plan_year' => 2026,
            'status' => 'review',
        ]);

        $session = TrainingSession::query()->create([
            'training_annual_plan_id' => $plan->id,
            'training_program_id' => $program->id,
            'title' => 'Delete Me Session',
            'scheduled_start_at' => '2026-05-01 10:00:00',
            'scheduled_end_at' => '2026-05-01 12:00:00',
            'status' => 'scheduled',
            'auto_fill_participants' => false,
        ]);

        $feedbackForm = TrainingFeedbackForm::query()->create([
            'training_session_id' => $session->id,
            'title' => 'Delete Me Feedback',
            'status' => 'open',
            'questions' => [
                ['type' => 'rating', 'text' => 'Question'],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->call('confirmDeleteFeedbackForm', $feedbackForm->id)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSee(__('training_needs::dashboard.confirmations.delete_feedback_form'))
            ->call('runConfirmedDeletion')
            ->assertSet('showDeleteConfirmation', false)
            ->call('confirmDeleteSession', $session->id)
            ->assertSee(__('training_needs::dashboard.confirmations.delete_session'))
            ->call('runConfirmedDeletion')
            ->call('confirmDeletePlan', $plan->id)
            ->assertSee(__('training_needs::dashboard.confirmations.delete_plan'))
            ->call('runConfirmedDeletion');

        $this->assertDatabaseMissing('training_feedback_forms', [
            'id' => $feedbackForm->id,
        ]);

        $this->assertDatabaseMissing('training_sessions', [
            'id' => $session->id,
        ]);

        $this->assertDatabaseMissing('training_annual_plans', [
            'id' => $plan->id,
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
