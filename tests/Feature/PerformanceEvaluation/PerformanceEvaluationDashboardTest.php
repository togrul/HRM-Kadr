<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceForm;
use App\Models\PerformanceFormScore;
use App\Models\PerformanceFormTemplate;
use App\Models\PerformanceFormTemplateItem;
use App\Models\PerformanceFormTemplateSection;
use App\Models\PerformanceCycle;
use App\Models\PerformanceTrainingNeedLink;
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
use App\Modules\PerformanceEvaluation\Livewire\EvaluationsSummary as PerformanceEvaluationEvaluationsSummary;
use App\Modules\PerformanceEvaluation\Livewire\EvaluatorScoreCapture;
use App\Modules\PerformanceEvaluation\Livewire\EvaluatorWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\Lists as PerformanceEvaluationLists;
use App\Modules\PerformanceEvaluation\Livewire\Overview as PerformanceEvaluationOverview;
use App\Modules\PerformanceEvaluation\Livewire\Reports as PerformanceEvaluationReports;
use App\Modules\PerformanceEvaluation\Livewire\TestWorkspace as PerformanceEvaluationTestWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\TestsSummary as PerformanceEvaluationTestsSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PerformanceEvaluationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_performance_evaluation_route(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantPerformancePermissions($user);

        $this->actingAs($user)
            ->get(route('performance-evaluation'))
            ->assertOk()
            ->assertSee(__('performance_evaluation::dashboard.title'));
    }

    public function test_overview_component_renders_performance_summary_cards(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantPerformancePermissions($user);

        $this->actingAs($user);

        Livewire::test(PerformanceEvaluationOverview::class)
            ->assertSee(__('performance_evaluation::dashboard.cards.foundation_scope'))
            ->assertSee(__('performance_evaluation::dashboard.cards.recent_cycles'))
            ->assertSee(__('performance_evaluation::dashboard.cards.reports'));
    }

    public function test_evaluations_summary_component_renders_recent_forms_and_relays_actions(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantPerformancePermissions($user);
        $manager = \App\Models\User::factory()->create(['name' => 'Team Manager']);
        $hrReviewer = \App\Models\User::factory()->create(['name' => 'HR Reviewer']);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 501,
            'name' => 'Specialist',
        ]);

        $personnel = $this->createPersonnel($user->id, 501, 'PE-501');
        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Cycle',
            'cycle_type' => 'annual',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'active',
        ]);
        $template = PerformanceFormTemplate::query()->create([
            'name' => 'Core Template',
            'code' => 'CORE-501',
            'is_active' => true,
        ]);
        $form = PerformanceForm::query()->create([
            'performance_cycle_id' => $cycle->id,
            'performance_form_template_id' => $template->id,
            'personnel_id' => $personnel->id,
            'manager_id' => $manager->id,
            'hr_reviewer_id' => $hrReviewer->id,
            'self_status' => 'submitted',
            'manager_status' => 'draft',
            'hr_status' => 'draft',
            'final_score' => 72.5,
            'final_category' => 'medium',
        ]);

        $this->actingAs($user);

        Livewire::test(PerformanceEvaluationEvaluationsSummary::class)
            ->assertSee(__('performance_evaluation::dashboard.cards.recent_forms'))
            ->assertSee($personnel->fullname)
            ->call('relayEditEvaluationForm', $form->id)
            ->assertDispatched('performance-evaluation:edit-form', formId: $form->id)
            ->call('relayDeleteEvaluationForm', $form->id)
            ->assertDispatched('performance-evaluation:confirm-delete-form', formId: $form->id);

        Livewire::test(Dashboard::class)
            ->dispatch('performance-evaluation:edit-form', formId: $form->id)
            ->assertSet('editingEvaluationFormId', $form->id)
            ->dispatch('performance-evaluation:confirm-delete-form', formId: $form->id)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSet('deleteConfirmation.action', 'deleteEvaluationForm');
    }

    public function test_dashboard_refreshes_evaluations_summary_after_form_assignment_changes(): void
    {
        $user = \App\Models\User::factory()->create();
        $manager = \App\Models\User::factory()->create(['name' => 'Team Manager']);
        $hrReviewer = \App\Models\User::factory()->create(['name' => 'HR Reviewer']);
        $this->grantPerformancePermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 502,
            'name' => 'Coordinator',
        ]);

        $personnel = $this->createPersonnel($user->id, 502, 'PE-502');
        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Summary Cycle',
            'cycle_type' => 'annual',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'active',
        ]);
        $template = PerformanceFormTemplate::query()->create([
            'name' => 'Summary Template',
            'code' => 'SUM-502',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class)
            ->assertSet('evaluationsSummaryVersion', 0)
            ->set('activeTab', 'evaluations')
            ->set('evaluationForm.performance_cycle_id', $cycle->id)
            ->set('evaluationForm.performance_form_template_id', $template->id)
            ->set('evaluationForm.personnel_id', $personnel->id)
            ->set('evaluationForm.manager_id', $manager->id)
            ->set('evaluationForm.hr_reviewer_id', $hrReviewer->id)
            ->call('storeEvaluationForm')
            ->assertHasNoErrors()
            ->assertSet('evaluationsSummaryVersion', 1);

        $formId = PerformanceForm::query()->where('personnel_id', $personnel->id)->value('id');

        $component
            ->call('deleteEvaluationForm', $formId)
            ->assertHasNoErrors()
            ->assertSet('evaluationsSummaryVersion', 2);
    }

    public function test_dashboard_refreshes_summary_islands_after_score_and_test_mutations(): void
    {
        $user = \App\Models\User::factory()->create();
        $manager = \App\Models\User::factory()->create(['name' => 'Team Manager']);
        $hrReviewer = \App\Models\User::factory()->create(['name' => 'HR Reviewer']);
        $this->grantPerformancePermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 503,
            'name' => 'Coordinator',
        ]);

        $personnel = $this->createPersonnel($user->id, 503, 'PE-503');
        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Score Cycle',
            'cycle_type' => 'annual',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'active',
        ]);
        $template = PerformanceFormTemplate::query()->create([
            'name' => 'Score Template',
            'code' => 'SCR-503',
            'is_active' => true,
        ]);
        $section = PerformanceFormTemplateSection::query()->create([
            'performance_form_template_id' => $template->id,
            'name' => 'Core Section',
            'weight_percent' => 100,
            'sort_order' => 1,
        ]);
        $item = PerformanceFormTemplateItem::query()->create([
            'performance_form_template_section_id' => $section->id,
            'name' => 'Delivery',
            'weight_percent' => 100,
            'low_score_threshold' => 60,
            'requires_comment' => true,
            'sort_order' => 1,
        ]);

        $form = PerformanceForm::query()->create([
            'performance_cycle_id' => $cycle->id,
            'performance_form_template_id' => $template->id,
            'personnel_id' => $personnel->id,
            'manager_id' => $manager->id,
            'hr_reviewer_id' => $hrReviewer->id,
            'self_status' => 'draft',
            'manager_status' => 'draft',
            'hr_status' => 'draft',
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('evaluationsSummaryVersion', 0)
            ->assertSet('testsSummaryVersion', 0)
            ->set('scoreForm.performance_form_id', $form->id)
            ->set('scoreForm.performance_form_template_item_id', $item->id)
            ->set('scoreForm.evaluator_type', 'manager')
            ->set('scoreForm.score', 77)
            ->call('storeScore')
            ->assertHasNoErrors()
            ->assertSet('evaluationsSummaryVersion', 1)
            ->set('bankForm.name', 'Review Bank')
            ->set('bankForm.code', 'RB-503')
            ->set('bankForm.pass_score', 70)
            ->set('bankForm.duration_minutes', 30)
            ->set('bankForm.max_attempts', 2)
            ->call('storeTestBank')
            ->assertHasNoErrors()
            ->assertSet('testsSummaryVersion', 1);
    }

    public function test_tests_summary_component_renders_testing_cards(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantPerformancePermissions($user);

        $this->actingAs($user);

        Livewire::test(PerformanceEvaluationTestsSummary::class)
            ->assertSee(__('performance_evaluation::dashboard.cards.recent_test_banks'))
            ->assertSee(__('performance_evaluation::dashboard.cards.recent_test_attempts'))
            ->assertSee(__('performance_evaluation::dashboard.cards.pending_review_answers'));
    }

    public function test_reports_component_renders_test_delivery_reporting_cards(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantPerformancePermissions($user);

        $this->actingAs($user);

        Livewire::test(PerformanceEvaluationReports::class)
            ->assertSee(__('performance_evaluation::dashboard.actions.export_forms_report'))
            ->assertSee(__('performance_evaluation::dashboard.cards.test_delivery_reports'))
            ->assertSee(__('performance_evaluation::dashboard.cards.recent_test_sessions'))
            ->assertSee(__('performance_evaluation::dashboard.cards.answer_audit'));
    }

    public function test_lists_component_supports_test_archive_entities(): void
    {
        $user = \App\Models\User::factory()->create();
        $reviewer = \App\Models\User::factory()->create(['name' => 'Reviewer User']);
        $this->grantPerformancePermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 513,
            'name' => 'Analyst',
        ]);

        $personnel = $this->createPersonnel($user->id, 513, 'PE-513');
        $competency = $this->createCompetency();
        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Test Archive Cycle',
            'cycle_type' => 'annual',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'active',
        ]);

        $bank = \App\Models\PerformanceTestBank::query()->create([
            'name' => 'Archive Bank',
            'code' => 'ARCH-513',
            'pass_score' => 70,
            'duration_minutes' => 30,
            'max_attempts' => 2,
            'is_active' => true,
        ]);

        $question = \App\Models\PerformanceTestQuestion::query()->create([
            'performance_test_bank_id' => $bank->id,
            'training_competency_id' => $competency->id,
            'question_type' => 'open_answer',
            'prompt' => 'How do you handle a critical incident?',
            'max_score' => 100,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $session = \App\Models\PerformanceTestSession::query()->create([
            'performance_cycle_id' => $cycle->id,
            'performance_test_bank_id' => $bank->id,
            'personnel_id' => $personnel->id,
            'reviewer_id' => $reviewer->id,
            'assigned_by' => $user->id,
            'scheduled_at' => '2026-03-13',
            'available_until' => '2026-03-20',
            'status' => 'assigned',
        ]);

        $attempt = \App\Models\PerformanceTestAttempt::query()->create([
            'performance_test_session_id' => $session->id,
            'attempt_no' => 1,
            'status' => 'review_pending',
            'score' => 77,
            'percentage' => 77,
        ]);

        $answer = \App\Models\PerformanceTestAttemptAnswer::query()->create([
            'performance_test_attempt_id' => $attempt->id,
            'performance_test_question_id' => $question->id,
            'answer_text' => 'I escalate and document the incident.',
            'review_status' => 'pending',
        ]);

        $this->actingAs($user);

        Livewire::test(PerformanceEvaluationLists::class)
            ->call('switchEntity', 'test_banks')
            ->assertSee('Archive Bank')
            ->call('switchEntity', 'test_questions')
            ->assertSee('How do you handle a critical incident?')
            ->call('selectRow', $question->id)
            ->assertSee('How do you handle a critical incident?')
            ->call('switchEntity', 'test_sessions')
            ->assertSee($personnel->fullname)
            ->call('switchEntity', 'test_answers')
            ->call('selectRow', $answer->id)
            ->assertSee('critical incident');
    }

    public function test_personnel_options_exclude_pending_duplicates(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantPerformancePermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 512,
            'name' => 'Inspector',
        ]);

        $activePersonnel = $this->createPersonnel($user->id, 512, 'DMX-26-000002');
        $pendingPersonnel = Personnel::query()->create([
            'surname' => $activePersonnel->surname,
            'name' => $activePersonnel->name,
            'patronymic' => $activePersonnel->patronymic,
            'tabel_no' => 'NMZD37',
            'birthdate' => optional($activePersonnel->birthdate)->format('Y-m-d') ?? '1990-01-01',
            'gender' => $activePersonnel->gender,
            'phone' => $activePersonnel->phone,
            'mobile' => '1234567',
            'email' => 'pending@example.com',
            'nationality_id' => 11,
            'pin' => '1234567',
            'residental_address' => 'ünvan',
            'education_degree_id' => 100,
            'structure_id' => $activePersonnel->structure_id,
            'position_id' => 512,
            'join_work_date' => '2026-03-13',
            'added_by' => $user->id,
            'work_norm_id' => 10,
            'is_pending' => true,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class);
        $labels = collect($component->instance()->personnelOptions())->pluck('label')->all();

        $this->assertContains($activePersonnel->fullname.' (#DMX-26-000002)', $labels);
        $this->assertNotContains($pendingPersonnel->fullname.' (#NMZD37)', $labels);
    }

    public function test_evaluator_score_capture_prefills_first_item_and_existing_score_when_opening_score_form(): void
    {
        $manager = \App\Models\User::factory()->create(['name' => 'Manager Reviewer']);
        $hrReviewer = \App\Models\User::factory()->create(['name' => 'HR Reviewer']);
        $this->grantPerformancePermissions($manager);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 511,
            'name' => 'Methodist',
        ]);

        $personnel = $this->createPersonnel($manager->id, 511, 'PE-511');
        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Reviewer Cycle',
            'cycle_type' => 'annual',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'active',
        ]);
        $template = PerformanceFormTemplate::query()->create([
            'name' => 'Reviewer Template',
            'code' => 'REV-511',
            'is_active' => true,
        ]);
        $section = PerformanceFormTemplateSection::query()->create([
            'performance_form_template_id' => $template->id,
            'name' => 'Core Section',
            'weight_percent' => 100,
            'sort_order' => 1,
        ]);
        $firstItem = PerformanceFormTemplateItem::query()->create([
            'performance_form_template_section_id' => $section->id,
            'name' => 'Communication',
            'weight_percent' => 50,
            'low_score_threshold' => 60,
            'requires_comment' => true,
            'sort_order' => 1,
        ]);
        $secondItem = PerformanceFormTemplateItem::query()->create([
            'performance_form_template_section_id' => $section->id,
            'name' => 'Delivery',
            'weight_percent' => 50,
            'low_score_threshold' => 60,
            'requires_comment' => true,
            'sort_order' => 2,
        ]);
        $form = PerformanceForm::query()->create([
            'performance_cycle_id' => $cycle->id,
            'performance_form_template_id' => $template->id,
            'personnel_id' => $personnel->id,
            'manager_id' => $manager->id,
            'hr_reviewer_id' => $hrReviewer->id,
            'self_status' => 'draft',
            'manager_status' => 'draft',
            'hr_status' => 'draft',
        ]);

        PerformanceFormScore::query()->create([
            'performance_form_id' => $form->id,
            'performance_form_template_item_id' => $firstItem->id,
            'evaluator_type' => 'manager',
            'score' => 88,
            'comment' => 'Existing manager note.',
        ]);

        PerformanceFormScore::query()->create([
            'performance_form_id' => $form->id,
            'performance_form_template_item_id' => $secondItem->id,
            'evaluator_type' => 'manager',
            'score' => 61,
            'comment' => 'Second item note.',
        ]);

        $this->actingAs($manager);

        Livewire::test(EvaluatorScoreCapture::class, [
            'formCatalog' => [[
                'id' => $form->id,
                'label' => $personnel->fullname.' / '.$template->name,
                'template_id' => $template->id,
                'evaluator_type' => 'manager',
            ]],
        ])
            ->call('startScoreForm', $form->id)
            ->assertSet('scoreForm.performance_form_id', $form->id)
            ->assertSet('scoreForm.evaluator_type', 'manager')
            ->assertSet('scoreForm.performance_form_template_item_id', $firstItem->id)
            ->assertSet('scoreForm.score', '88.00')
            ->assertSet('scoreForm.comment', 'Existing manager note.')
            ->set('scoreForm.performance_form_template_item_id', $secondItem->id)
            ->assertSet('scoreForm.score', '61.00')
            ->assertSet('scoreForm.comment', 'Second item note.');
    }

    public function test_performance_evaluation_route_requires_view_permission(): void
    {
        $user = \App\Models\User::factory()->create();
        Permission::findOrCreate('show-performance-evaluation', 'web');

        $this->actingAs($user)
            ->get(route('performance-evaluation'))
            ->assertForbidden();
    }

    public function test_personnel_can_open_and_submit_assigned_test_in_test_workspace(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Ramin Huseynov',
            'email' => 'test-taker@example.com ',
        ]);

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 514,
            'name' => 'Operator',
        ]);

        $personnel = $this->createPersonnel($user->id, 514, 'PE-514');

        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Taker Cycle',
            'cycle_type' => 'annual',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'active',
        ]);

        $bank = \App\Models\PerformanceTestBank::query()->create([
            'name' => 'User Test Bank',
            'code' => 'UTB-514',
            'pass_score' => 60,
            'duration_minutes' => 20,
            'max_attempts' => 1,
            'is_active' => true,
        ]);

        $question = \App\Models\PerformanceTestQuestion::query()->create([
            'performance_test_bank_id' => $bank->id,
            'question_type' => 'multiple_choice',
            'prompt' => 'Choose the correct option',
            'max_score' => 100,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $option = \App\Models\PerformanceTestQuestionOption::query()->create([
            'performance_test_question_id' => $question->id,
            'label' => 'Correct option',
            'is_correct' => true,
            'score_value' => 100,
            'sort_order' => 1,
        ]);

        $session = \App\Models\PerformanceTestSession::query()->create([
            'performance_cycle_id' => $cycle->id,
            'performance_test_bank_id' => $bank->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => 1,
            'scheduled_at' => '2026-03-13',
            'available_until' => '2026-03-20',
            'status' => 'assigned',
        ]);

        $this->actingAs($user)
            ->get(route('performance-evaluation.test-workspace'))
            ->assertOk()
            ->assertSee(__('performance_evaluation::dashboard.cards.test_taking_workspace'))
            ->assertSee('User Test Bank');

        Livewire::test(PerformanceEvaluationTestWorkspace::class)
            ->call('openSession', $session->id)
            ->set("answers.{$question->id}.selected_option_id", $option->id)
            ->call('submitAttempt')
            ->assertHasNoErrors();

        $attempt = \App\Models\PerformanceTestAttempt::query()->where('performance_test_session_id', $session->id)->firstOrFail();

        $this->assertDatabaseHas('performance_test_attempt_answers', [
            'performance_test_attempt_id' => $attempt->id,
            'performance_test_question_id' => $question->id,
            'selected_option_id' => $option->id,
        ]);
    }

    public function test_test_workspace_supports_question_navigation_timer_and_attempt_history(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Ramin Huseynov',
            'email' => 'workspace-user@example.com',
        ]);
        $this->grantPerformancePermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 515,
            'name' => 'Operator',
        ]);

        $personnel = $this->createPersonnel($user->id, 515, 'PE-515');

        $bank = \App\Models\PerformanceTestBank::query()->create([
            'name' => 'Workspace Bank',
            'code' => 'WB-515',
            'pass_score' => 60,
            'duration_minutes' => 25,
            'max_attempts' => 2,
            'is_active' => true,
        ]);

        $questionOne = \App\Models\PerformanceTestQuestion::query()->create([
            'performance_test_bank_id' => $bank->id,
            'question_type' => 'multiple_choice',
            'prompt' => 'First question',
            'max_score' => 100,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $questionTwo = \App\Models\PerformanceTestQuestion::query()->create([
            'performance_test_bank_id' => $bank->id,
            'question_type' => 'open_answer',
            'prompt' => 'Second question',
            'max_score' => 100,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $option = \App\Models\PerformanceTestQuestionOption::query()->create([
            'performance_test_question_id' => $questionOne->id,
            'label' => 'Correct option',
            'is_correct' => true,
            'score_value' => 100,
            'sort_order' => 1,
        ]);

        $session = \App\Models\PerformanceTestSession::query()->create([
            'performance_test_bank_id' => $bank->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'scheduled_at' => '2026-03-13',
            'available_until' => '2026-03-20',
            'status' => 'assigned',
        ]);

        $attempt = \App\Models\PerformanceTestAttempt::query()->create([
            'performance_test_session_id' => $session->id,
            'attempt_no' => 1,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now()->subMinutes(2),
            'duration_seconds' => 180,
            'score' => 75,
            'percentage' => 75,
            'passed' => true,
            'status' => 'completed',
        ]);

        \App\Models\PerformanceTestAttemptAnswer::query()->create([
            'performance_test_attempt_id' => $attempt->id,
            'performance_test_question_id' => $questionOne->id,
            'selected_option_id' => $option->id,
            'review_status' => 'auto_ready',
            'auto_score' => 100,
            'final_score' => 100,
        ]);

        $this->actingAs($user);

        Livewire::test(PerformanceEvaluationTestWorkspace::class)
            ->assertSet('currentQuestionId', $questionTwo->id)
            ->assertSee(__('performance_evaluation::dashboard.cards.attempt_history'))
            ->assertSee(__('performance_evaluation::dashboard.fields.remaining_time'))
            ->call('openQuestion', $questionTwo->id)
            ->assertSet('currentQuestionId', $questionTwo->id)
            ->call('beginAttempt')
            ->assertHasNoErrors();
    }

    public function test_test_workspace_stops_timer_after_completed_attempt_and_prefers_actionable_session(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Ramin Huseynov',
            'email' => 'nigar@example.com',
        ]);
        $this->grantPerformancePermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 516,
            'name' => 'Analyst',
        ]);

        $personnel = $this->createPersonnel($user->id, 516, 'PE-516');

        $bank = \App\Models\PerformanceTestBank::query()->create([
            'name' => 'Priority Bank',
            'code' => 'PB-516',
            'pass_score' => 60,
            'duration_minutes' => 30,
            'max_attempts' => 1,
            'is_active' => true,
        ]);

        $question = \App\Models\PerformanceTestQuestion::query()->create([
            'performance_test_bank_id' => $bank->id,
            'question_type' => 'open_answer',
            'prompt' => 'Explain the scenario',
            'max_score' => 100,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $completedSession = \App\Models\PerformanceTestSession::query()->create([
            'performance_test_bank_id' => $bank->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'available_until' => '2026-03-20',
            'status' => 'completed',
        ]);

        $completedAttempt = \App\Models\PerformanceTestAttempt::query()->create([
            'performance_test_session_id' => $completedSession->id,
            'attempt_no' => 1,
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now()->subMinutes(5),
            'duration_seconds' => 1500,
            'score' => 90,
            'percentage' => 90,
            'passed' => true,
            'status' => 'completed',
        ]);

        \App\Models\PerformanceTestAttemptAnswer::query()->create([
            'performance_test_attempt_id' => $completedAttempt->id,
            'performance_test_question_id' => $question->id,
            'answer_text' => 'Done',
            'review_status' => 'reviewed',
            'final_score' => 90,
        ]);

        $assignedSession = \App\Models\PerformanceTestSession::query()->create([
            'performance_test_bank_id' => $bank->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'available_until' => '2026-03-21',
            'status' => 'assigned',
        ]);

        $this->actingAs($user);

        Livewire::test(PerformanceEvaluationTestWorkspace::class)
            ->assertSet('selectedSessionId', $assignedSession->id)
            ->call('openSession', $completedSession->id)
            ->assertSet('selectedSessionId', $completedSession->id)
            ->assertSet('sessionTimer.finished', true)
            ->assertSet('sessionTimer.remaining_seconds', 0)
            ->assertSet('selectedSessionReadOnly', true);
    }

    public function test_test_workspace_auto_saves_flags_and_switches_to_next_session_after_submit(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Ramin Huseynov',
            'email' => 'aysel@example.com',
        ]);
        $this->grantPerformancePermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 517,
            'name' => 'Planner',
        ]);

        $personnel = $this->createPersonnel($user->id, 517, 'PE-517');

        $bank = \App\Models\PerformanceTestBank::query()->create([
            'name' => 'Switch Bank',
            'code' => 'SB-517',
            'pass_score' => 60,
            'duration_minutes' => 20,
            'max_attempts' => 1,
            'is_active' => true,
        ]);

        $questionOne = \App\Models\PerformanceTestQuestion::query()->create([
            'performance_test_bank_id' => $bank->id,
            'question_type' => 'multiple_choice',
            'prompt' => 'Choose correctly',
            'max_score' => 100,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $option = \App\Models\PerformanceTestQuestionOption::query()->create([
            'performance_test_question_id' => $questionOne->id,
            'label' => 'Correct answer',
            'is_correct' => true,
            'score_value' => 100,
            'sort_order' => 1,
        ]);

        $firstSession = \App\Models\PerformanceTestSession::query()->create([
            'performance_test_bank_id' => $bank->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'available_until' => '2026-03-22',
            'status' => 'assigned',
        ]);

        $nextSession = \App\Models\PerformanceTestSession::query()->create([
            'performance_test_bank_id' => $bank->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'available_until' => '2026-03-23',
            'status' => 'assigned',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(PerformanceEvaluationTestWorkspace::class)
            ->call('openSession', $firstSession->id)
            ->call('beginAttempt')
            ->call('toggleQuestionFlag', $questionOne->id)
            ->set("answers.{$questionOne->id}.selected_option_id", $option->id)
            ->call('flushAutoSave')
            ->call('saveDraft')
            ->call('submitAttempt')
            ->assertHasNoErrors()
            ->assertSet('selectedSessionId', $nextSession->id);

        $attempt = \App\Models\PerformanceTestAttempt::query()
            ->where('performance_test_session_id', $firstSession->id)
            ->firstOrFail();

        $this->assertSame([$questionOne->id], data_get($attempt->fresh()->meta, 'flagged_question_ids'));
        $this->assertDatabaseHas('performance_test_attempt_answers', [
            'performance_test_attempt_id' => $attempt->id,
            'performance_test_question_id' => $questionOne->id,
            'selected_option_id' => $option->id,
        ]);
    }

    public function test_test_workspace_auto_submits_expired_attempt(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Ramin Huseynov',
            'email' => 'orxan@example.com',
        ]);
        $this->grantPerformancePermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 518,
            'name' => 'Coordinator',
        ]);

        $personnel = $this->createPersonnel($user->id, 518, 'PE-518');

        $bank = \App\Models\PerformanceTestBank::query()->create([
            'name' => 'Expiry Bank',
            'code' => 'EB-518',
            'pass_score' => 60,
            'duration_minutes' => 1,
            'max_attempts' => 1,
            'is_active' => true,
        ]);

        $question = \App\Models\PerformanceTestQuestion::query()->create([
            'performance_test_bank_id' => $bank->id,
            'question_type' => 'multiple_choice',
            'prompt' => 'Expired question',
            'max_score' => 100,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $option = \App\Models\PerformanceTestQuestionOption::query()->create([
            'performance_test_question_id' => $question->id,
            'label' => 'Answer',
            'is_correct' => true,
            'score_value' => 100,
            'sort_order' => 1,
        ]);

        $session = \App\Models\PerformanceTestSession::query()->create([
            'performance_test_bank_id' => $bank->id,
            'personnel_id' => $personnel->id,
            'assigned_by' => $user->id,
            'available_until' => '2026-03-24',
            'status' => 'in_progress',
        ]);

        $attempt = \App\Models\PerformanceTestAttempt::query()->create([
            'performance_test_session_id' => $session->id,
            'attempt_no' => 1,
            'started_at' => now()->subMinutes(5),
            'status' => 'draft',
            'meta' => ['flagged_question_ids' => []],
        ]);

        \App\Models\PerformanceTestAttemptAnswer::query()->create([
            'performance_test_attempt_id' => $attempt->id,
            'performance_test_question_id' => $question->id,
            'selected_option_id' => $option->id,
            'review_status' => 'auto_ready',
        ]);

        $this->actingAs($user);

        Livewire::test(PerformanceEvaluationTestWorkspace::class)
            ->call('openSession', $session->id)
            ->call('autoSubmitExpiredAttempt')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('performance_test_attempts', [
            'id' => $attempt->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('performance_test_sessions', [
            'id' => $session->id,
            'status' => 'completed',
        ]);

        Livewire::test(PerformanceEvaluationTestWorkspace::class)
            ->call('openSession', $session->id)
            ->assertSet('selectedSessionReadOnly', true)
            ->assertSet('sessionTimer.finished', true);
    }

    public function test_dashboard_can_create_foundation_records_and_link_weak_score_to_training_need(): void
    {
        $user = \App\Models\User::factory()->create(['name' => 'HR Specialist']);
        $manager = \App\Models\User::factory()->create(['name' => 'Team Manager']);
        $hrReviewer = \App\Models\User::factory()->create(['name' => 'Reviewer User']);
        $this->grantPerformancePermissions($user);

        $beginner = TrainingLevel::query()->where('score', 2)->firstOrFail();
        $targetLevel = TrainingLevel::query()->where('score', 4)->firstOrFail();

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 301,
            'name' => 'Senior Lecturer',
        ]);

        $personnel = $this->createPersonnel($user->id, 301);
        $competency = $this->createCompetency();
        $program = $this->createTrainingProgram($competency->id, $targetLevel->id);

        RoleCompetencyRequirement::query()->create([
            'position_id' => 301,
            'training_competency_id' => $competency->id,
            'required_level_id' => $targetLevel->id,
            'priority' => 'high',
            'is_mandatory' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('cycleForm.name', '2026 Academic Performance')
            ->set('cycleForm.cycle_type', 'academic')
            ->set('cycleForm.period_start', '2026-09-01')
            ->set('cycleForm.period_end', '2027-06-30')
            ->set('cycleForm.status', 'active')
            ->call('storeCycle')
            ->assertHasNoErrors()
            ->set('templateForm.name', 'Academic Staff Core Template')
            ->set('templateForm.code', 'PE-AC-01')
            ->call('storeTemplate')
            ->assertHasNoErrors()
            ->set('sectionForm.performance_form_template_id', PerformanceFormTemplate::query()->where('code', 'PE-AC-01')->value('id'))
            ->set('sectionForm.name', 'Pedagogical Performance')
            ->set('sectionForm.weight_percent', 60)
            ->call('storeSection')
            ->assertHasNoErrors()
            ->set('itemForm.performance_form_template_section_id', PerformanceFormTemplateSection::query()->where('name', 'Pedagogical Performance')->value('id'))
            ->set('itemForm.training_competency_id', $competency->id)
            ->set('itemForm.name', 'Teaching Methodology')
            ->set('itemForm.weight_percent', 30)
            ->set('itemForm.low_score_threshold', 60)
            ->call('storeItem')
            ->assertHasNoErrors()
            ->set('evaluationForm.performance_cycle_id', \App\Models\PerformanceCycle::query()->where('name', '2026 Academic Performance')->value('id'))
            ->set('evaluationForm.performance_form_template_id', PerformanceFormTemplate::query()->where('code', 'PE-AC-01')->value('id'))
            ->set('evaluationForm.personnel_id', $personnel->id)
            ->set('evaluationForm.manager_id', $manager->id)
            ->set('evaluationForm.hr_reviewer_id', $hrReviewer->id)
            ->call('storeEvaluationForm')
            ->assertHasNoErrors()
            ->set('scoreForm.performance_form_id', PerformanceForm::query()->where('personnel_id', $personnel->id)->value('id'))
            ->set('scoreForm.performance_form_template_item_id', PerformanceFormTemplateItem::query()->where('name', 'Teaching Methodology')->value('id'))
            ->set('scoreForm.evaluator_type', 'manager')
            ->set('scoreForm.score', 45)
            ->set('scoreForm.comment', 'Methodology consistency is below expected standard.')
            ->call('storeScore')
            ->assertHasNoErrors();

        $formId = PerformanceForm::query()->where('personnel_id', $personnel->id)->value('id');
        $scoreId = \App\Models\PerformanceFormScore::query()->where('performance_form_id', $formId)->value('id');

        $this->assertDatabaseHas('performance_cycles', [
            'name' => '2026 Academic Performance',
            'cycle_type' => 'academic',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('performance_form_templates', [
            'name' => 'Academic Staff Core Template',
            'code' => 'PE-AC-01',
        ]);

        $this->assertDatabaseHas('performance_forms', [
            'id' => $formId,
            'manager_id' => $manager->id,
            'hr_reviewer_id' => $hrReviewer->id,
            'manager_status' => 'submitted',
            'final_category' => 'weak',
        ]);

        $this->assertDatabaseHas('performance_form_scores', [
            'id' => $scoreId,
            'evaluator_type' => 'manager',
        ]);

        $this->assertDatabaseHas('training_need_items', [
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'recommended_program_id' => $program->id,
            'target_level_id' => $targetLevel->id,
            'source' => 'performance_gap',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('performance_training_need_links', [
            'performance_form_id' => $formId,
            'performance_form_score_id' => $scoreId,
            'training_competency_id' => $competency->id,
        ]);

        Livewire::test(Dashboard::class)
            ->set('scoreForm.performance_form_id', $formId)
            ->set('scoreForm.performance_form_template_item_id', PerformanceFormTemplateItem::query()->where('name', 'Teaching Methodology')->value('id'))
            ->set('scoreForm.evaluator_type', 'manager')
            ->set('scoreForm.score', 82)
            ->set('scoreForm.comment', 'Recovered after coaching.')
            ->call('storeScore')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('performance_training_need_links', [
            'performance_form_score_id' => $scoreId,
        ]);

        $this->assertSame(0, TrainingNeedItem::query()->where('source', 'performance_gap')->count());
        $this->assertSame(0, PerformanceTrainingNeedLink::query()->count());
    }

    public function test_existing_medium_performance_need_is_upgraded_when_final_result_becomes_weak(): void
    {
        $user = \App\Models\User::factory()->create(['name' => 'HR Specialist']);
        $manager = \App\Models\User::factory()->create(['name' => 'Team Manager']);
        $hrReviewer = \App\Models\User::factory()->create(['name' => 'Reviewer User']);
        $this->grantPerformancePermissions($user);

        $beginner = TrainingLevel::query()->where('score', 2)->firstOrFail();
        $targetLevel = TrainingLevel::query()->where('score', 4)->firstOrFail();

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 302,
            'name' => 'Senior Lecturer',
        ]);

        $personnel = $this->createPersonnel($user->id, 302, 'PE-002');
        $competency = $this->createCompetency();
        $program = $this->createTrainingProgram($competency->id, $targetLevel->id);

        RoleCompetencyRequirement::query()->create([
            'position_id' => 302,
            'training_competency_id' => $competency->id,
            'required_level_id' => $targetLevel->id,
            'priority' => 'high',
            'is_mandatory' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('cycleForm.name', '2026 Annual Performance')
            ->set('cycleForm.cycle_type', 'annual')
            ->set('cycleForm.period_start', '2026-01-01')
            ->set('cycleForm.period_end', '2026-12-31')
            ->set('cycleForm.status', 'active')
            ->call('storeCycle')
            ->assertHasNoErrors()
            ->set('templateForm.name', 'Annual Staff Template')
            ->set('templateForm.code', 'PE-AN-01')
            ->call('storeTemplate')
            ->assertHasNoErrors()
            ->set('sectionForm.performance_form_template_id', PerformanceFormTemplate::query()->where('code', 'PE-AN-01')->value('id'))
            ->set('sectionForm.name', 'Core Criteria')
            ->set('sectionForm.weight_percent', 100)
            ->call('storeSection')
            ->assertHasNoErrors();

        $sectionId = PerformanceFormTemplateSection::query()->where('name', 'Core Criteria')->value('id');

        Livewire::test(Dashboard::class)
            ->set('itemForm.performance_form_template_section_id', $sectionId)
            ->set('itemForm.training_competency_id', $competency->id)
            ->set('itemForm.name', 'Criterion A')
            ->set('itemForm.weight_percent', 50)
            ->set('itemForm.low_score_threshold', 70)
            ->call('storeItem')
            ->assertHasNoErrors()
            ->set('itemForm.performance_form_template_section_id', $sectionId)
            ->set('itemForm.training_competency_id', $competency->id)
            ->set('itemForm.name', 'Criterion B')
            ->set('itemForm.weight_percent', 50)
            ->set('itemForm.low_score_threshold', 60)
            ->call('storeItem')
            ->assertHasNoErrors()
            ->set('evaluationForm.performance_cycle_id', \App\Models\PerformanceCycle::query()->where('name', '2026 Annual Performance')->value('id'))
            ->set('evaluationForm.performance_form_template_id', PerformanceFormTemplate::query()->where('code', 'PE-AN-01')->value('id'))
            ->set('evaluationForm.personnel_id', $personnel->id)
            ->set('evaluationForm.manager_id', $manager->id)
            ->set('evaluationForm.hr_reviewer_id', $hrReviewer->id)
            ->call('storeEvaluationForm')
            ->assertHasNoErrors();

        $formId = PerformanceForm::query()->where('personnel_id', $personnel->id)->value('id');
        $itemAId = PerformanceFormTemplateItem::query()->where('name', 'Criterion A')->value('id');
        $itemBId = PerformanceFormTemplateItem::query()->where('name', 'Criterion B')->value('id');

        Livewire::test(Dashboard::class)
            ->set('scoreForm.performance_form_id', $formId)
            ->set('scoreForm.performance_form_template_item_id', $itemAId)
            ->set('scoreForm.evaluator_type', 'manager')
            ->set('scoreForm.score', 65)
            ->call('storeScore')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_need_items', [
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'priority' => 'medium',
        ]);

        Livewire::test(Dashboard::class)
            ->set('scoreForm.performance_form_id', $formId)
            ->set('scoreForm.performance_form_template_item_id', $itemBId)
            ->set('scoreForm.evaluator_type', 'hr')
            ->set('scoreForm.score', 30)
            ->call('storeScore')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('performance_forms', [
            'id' => $formId,
            'final_category' => 'weak',
        ]);

        $this->assertDatabaseHas('training_need_items', [
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'recommended_program_id' => $program->id,
            'target_level_id' => $targetLevel->id,
            'priority' => 'high',
        ]);
    }

    public function test_weak_links_list_renders_presented_reason_in_active_locale(): void
    {
        app()->setLocale('az');

        $user = \App\Models\User::factory()->create(['name' => 'HR Specialist']);
        $this->grantPerformancePermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create([
            'id' => 303,
            'name' => 'Senior Lecturer',
        ]);

        $personnel = $this->createPersonnel($user->id, 303, 'PE-003');
        $competency = $this->createCompetency();
        $program = $this->createTrainingProgram($competency->id, TrainingLevel::query()->where('score', 4)->value('id'));

        $need = TrainingNeedItem::query()->create([
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'position_id' => 303,
            'recommended_program_id' => $program->id,
            'target_level_id' => TrainingLevel::query()->where('score', 4)->value('id'),
            'priority' => 'high',
            'source' => 'performance_gap',
            'status' => 'approved',
            'target_completion_date' => '2026-05-01',
            'reason' => 'Low performance score detected on form #1, item #2: 45.00',
        ]);

        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Annual Performance',
            'cycle_type' => 'annual',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'active',
        ]);

        $template = PerformanceFormTemplate::query()->create([
            'name' => 'Annual Staff Template',
            'code' => 'PE-AN-01',
            'is_active' => true,
        ]);

        $section = PerformanceFormTemplateSection::query()->create([
            'performance_form_template_id' => $template->id,
            'name' => 'Core Criteria',
            'weight_percent' => 100,
        ]);

        $item = PerformanceFormTemplateItem::query()->create([
            'performance_form_template_section_id' => $section->id,
            'training_competency_id' => $competency->id,
            'name' => 'Criterion A',
            'weight_percent' => 100,
            'low_score_threshold' => 70,
        ]);

        $form = PerformanceForm::query()->create([
            'performance_cycle_id' => $cycle->id,
            'performance_form_template_id' => $template->id,
            'personnel_id' => $personnel->id,
            'final_score' => 45,
            'final_category' => 'weak',
            'result_status' => 'completed',
        ]);

        $score = PerformanceFormScore::query()->create([
            'performance_form_id' => $form->id,
            'performance_form_template_item_id' => $item->id,
            'evaluator_type' => 'manager',
            'score' => 45,
        ]);

        $link = PerformanceTrainingNeedLink::query()->create([
            'performance_form_id' => $form->id,
            'performance_form_score_id' => $score->id,
            'training_need_item_id' => $need->id,
            'training_competency_id' => $competency->id,
            'source' => 'low_score',
        ]);

        $this->actingAs($user);

        Livewire::test(PerformanceEvaluationLists::class)
            ->set('entity', 'weak_links')
            ->set('selectedRowId', $link->id)
            ->assertSee(__('performance_evaluation::dashboard.messages.performance_gap_reason', [
                'form' => 1,
                'item' => 2,
                'score' => '45.00',
            ]));
    }

    public function test_cycle_delete_is_confirmed_via_modal_before_execution(): void
    {
        $user = \App\Models\User::factory()->create(['name' => 'HR Specialist']);
        $this->grantPerformancePermissions($user);

        $cycle = PerformanceCycle::query()->create([
            'name' => '2026 Annual Performance',
            'cycle_type' => 'annual',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'draft',
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->call('confirmDeleteCycle', $cycle->id)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSee(__('performance_evaluation::dashboard.confirmations.delete_cycle'))
            ->assertSee('2026 Annual Performance')
            ->call('runConfirmedDeletion')
            ->assertSet('showDeleteConfirmation', false);

        $this->assertDatabaseMissing('performance_cycles', [
            'id' => $cycle->id,
        ]);
    }

    private function createCompetency(): TrainingCompetency
    {
        $group = TrainingCompetencyGroup::query()->create([
            'name' => 'Pedagogy',
            'slug' => 'pedagogy',
            'is_active' => true,
        ]);

        return TrainingCompetency::query()->create([
            'training_competency_group_id' => $group->id,
            'name' => 'Teaching Excellence',
            'slug' => 'teaching-excellence',
            'is_active' => true,
        ]);
    }

    private function createTrainingProgram(int $competencyId, int $targetLevelId): TrainingProgram
    {
        $program = TrainingProgram::query()->create([
            'title' => 'Advanced Teaching Lab',
            'slug' => 'advanced-teaching-lab',
            'code' => 'TRN-401',
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

    private function createPersonnel(int $userId, int $positionId, string $tabelNo = 'PE-001'): Personnel
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
            'tabel_no' => $tabelNo,
            'surname' => 'Huseynov',
            'name' => 'Ramin',
            'patronymic' => 'Elchin',
            'birthdate' => '1991-03-15',
            'gender' => 1,
            'phone' => '0121111111',
            'mobile' => '0501111111',
            'email' => 'ramin@example.test',
            'nationality_id' => 1,
            'pin' => 'XYZ1234',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => $positionId,
            'work_norm_id' => 1,
            'join_work_date' => '2024-01-01',
            'added_by' => $userId,
        ]);
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
}
