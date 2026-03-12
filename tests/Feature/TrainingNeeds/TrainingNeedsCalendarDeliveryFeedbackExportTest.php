<?php

namespace Tests\Feature\TrainingNeeds;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\TrainingAnnualPlan;
use App\Models\TrainingCompetency;
use App\Models\TrainingCompetencyGroup;
use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackForm;
use App\Models\TrainingFeedbackResponse;
use App\Models\TrainingLevel;
use App\Models\TrainingNeedItem;
use App\Models\TrainingPlanItem;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedReportingService;
use App\Modules\TrainingNeeds\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingNeedsCalendarDeliveryFeedbackExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_status_promotes_and_training_delivery_flow_is_persisted(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create(['id' => 402, 'name' => 'Trainer']);
        $personnel = $this->createPersonnel($user->id, 402, 'TN-550', 'Aysel');

        $group = TrainingCompetencyGroup::query()->create([
            'name' => 'Delivery',
            'slug' => 'delivery',
            'is_active' => true,
        ]);

        $level = TrainingLevel::query()->where('score', 4)->firstOrFail();

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
            'position_id' => 402,
            'recommended_program_id' => $program->id,
            'target_level_id' => $level->id,
            'priority' => 'high',
            'source' => 'manager_request',
            'status' => 'approved',
            'target_completion_date' => '2026-05-10',
            'reason' => 'Need presentation practice',
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'planning')
            ->set('planForm.title', '2026 Delivery Plan')
            ->set('planForm.plan_year', 2026)
            ->set('planForm.status', 'draft')
            ->set('planForm.auto_generate', true)
            ->call('storePlan')
            ->assertHasNoErrors();

        $plan = TrainingAnnualPlan::query()->where('title', '2026 Delivery Plan')->firstOrFail();
        $this->assertSame('review', $plan->status);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'calendar')
            ->set('sessionForm.training_annual_plan_id', $plan->id)
            ->set('sessionForm.training_program_id', $program->id)
            ->set('sessionForm.scheduled_start_at', '2026-05-10T09:00')
            ->set('sessionForm.scheduled_end_at', '2026-05-10T15:00')
            ->set('sessionForm.location', 'DMX Akademiya')
            ->set('sessionForm.trainer_name', 'Aynur Məmmədova')
            ->set('sessionForm.capacity', 20)
            ->set('sessionForm.planned_budget', 450)
            ->call('storeSession')
            ->assertHasNoErrors();

        $session = TrainingSession::query()->where('training_annual_plan_id', $plan->id)->firstOrFail();

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'calendar')
            ->set('participantForm.training_session_id', $session->id)
            ->set('participantForm.personnel_id', $personnel->id)
            ->set('participantForm.training_need_item_id', $need->id)
            ->set('participantForm.attendance_status', 'attended')
            ->call('storeSessionParticipant')
            ->assertHasNoErrors()
            ->set('participantForm.training_session_id', $session->id)
            ->call('completeSession')
            ->assertHasNoErrors();

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'results')
            ->set('feedbackForm.training_session_id', $session->id)
            ->set('feedbackForm.title', 'Presentation Lab Feedback')
            ->set('feedbackForm.questions_text', "Trainer preparedness\nMaterial usefulness")
            ->call('storeFeedbackForm')
            ->assertHasNoErrors();

        $feedbackForm = TrainingFeedbackForm::query()->where('training_session_id', $session->id)->firstOrFail();

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'results')
            ->set('feedbackResponseForm.training_feedback_form_id', $feedbackForm->id)
            ->set('feedbackResponseForm.personnel_id', $personnel->id)
            ->set('feedbackResponseForm.overall_score', 5)
            ->set('feedbackResponseForm.comments', 'Useful training')
            ->set('feedbackResponseForm.answers_text', "Great trainer\nUseful materials")
            ->call('submitFeedbackResponse')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_sessions', [
            'id' => $session->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('training_delivery_records', [
            'training_session_id' => $session->id,
            'personnel_id' => $personnel->id,
        ]);

        $this->assertDatabaseHas('training_feedback_responses', [
            'training_feedback_form_id' => $feedbackForm->id,
            'personnel_id' => $personnel->id,
            'overall_score' => 5,
        ]);

        $this->assertSame('completed', $need->fresh()->status);
        $this->assertSame(1, TrainingDeliveryRecord::query()->count());
        $this->assertSame(1, TrainingFeedbackResponse::query()->count());

        $reporting = app(TrainingNeedReportingService::class);
        $this->assertCount(1, $reporting->deliveryRows());
        $this->assertCount(1, $reporting->feedbackRows());
        $this->assertCount(1, $reporting->feedbackSessionSummaries());
        $this->assertSame(5.0, (float) $reporting->feedbackSessionSummaries()->first()->average_feedback_score);
    }

    public function test_session_can_auto_fill_participants_and_delivery_certificate_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create(['id' => 403, 'name' => 'Analyst']);
        $personnel = $this->createPersonnel($user->id, 403, 'TN-551', 'Nigar');

        $group = TrainingCompetencyGroup::query()->create([
            'name' => 'Analysis',
            'slug' => 'analysis',
            'is_active' => true,
        ]);

        $level = TrainingLevel::query()->where('score', 3)->firstOrFail();

        $competency = TrainingCompetency::query()->create([
            'training_competency_group_id' => $group->id,
            'name' => 'Excel Analysis',
            'slug' => 'excel-analysis',
            'is_active' => true,
        ]);

        $program = TrainingProgram::query()->create([
            'title' => 'Excel Deep Dive',
            'slug' => 'excel-deep-dive',
            'delivery_type' => 'internal',
            'duration_hours' => 8,
            'is_active' => true,
        ]);

        $need = TrainingNeedItem::query()->create([
            'personnel_id' => $personnel->id,
            'training_competency_id' => $competency->id,
            'position_id' => 403,
            'recommended_program_id' => $program->id,
            'target_level_id' => $level->id,
            'priority' => 'medium',
            'source' => 'manager_request',
            'status' => 'approved',
            'target_completion_date' => '2026-06-15',
            'reason' => 'Needs stronger spreadsheet analysis',
        ]);

        $plan = TrainingAnnualPlan::query()->create([
            'title' => '2026 Q2 Plan',
            'plan_year' => 2026,
            'plan_quarter' => 2,
            'status' => 'review',
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'calendar')
            ->set('sessionForm.training_annual_plan_id', $plan->id)
            ->set('sessionForm.training_program_id', $program->id)
            ->set('sessionForm.scheduled_start_at', '2026-06-10T09:00')
            ->set('sessionForm.scheduled_end_at', '2026-06-10T17:00')
            ->set('sessionForm.location', 'Data Lab')
            ->set('sessionForm.auto_fill_participants', true)
            ->call('storeSession')
            ->assertHasNoErrors();

        $session = TrainingSession::query()->latest('id')->firstOrFail();

        $this->assertTrue($session->auto_fill_participants);
        $this->assertDatabaseHas('training_session_participants', [
            'training_session_id' => $session->id,
            'personnel_id' => $personnel->id,
            'training_need_item_id' => $need->id,
            'attendance_status' => 'planned',
        ]);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'calendar')
            ->set('participantForm.training_session_id', $session->id)
            ->set('participantForm.personnel_id', $personnel->id)
            ->set('participantForm.training_need_item_id', $need->id)
            ->set('participantForm.attendance_status', 'attended')
            ->call('storeSessionParticipant')
            ->assertHasNoErrors();

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'calendar')
            ->set('participantForm.training_session_id', $session->id)
            ->call('completeSession')
            ->assertHasNoErrors();

        $record = TrainingDeliveryRecord::query()->where('training_session_id', $session->id)->firstOrFail();

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'results')
            ->set('deliveryDocumentForm.training_delivery_record_id', $record->id)
            ->set('deliveryDocumentForm.certificate_file', UploadedFile::fake()->image('certificate.jpg'))
            ->call('storeDeliveryDocument')
            ->assertHasNoErrors();

        $record->refresh();

        $this->assertNotNull($record->certificate_path);
        $this->assertSame('certificate.jpg', $record->certificate_name);
        Storage::disk('public')->assertExists($record->certificate_path);
        $this->assertSame('certificate.jpg', app(TrainingNeedReportingService::class)->deliveryRows()->first()->certificate_name);
    }

    public function test_feedback_form_default_question_type_is_saved_into_question_payload(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create(['id' => 404, 'name' => 'Lecturer']);
        $this->createPersonnel($user->id, 404, 'TN-552', 'Laman');

        $session = TrainingSession::query()->create([
            'title' => 'Soft Skills Session',
            'scheduled_start_at' => '2026-07-01 09:00:00',
            'status' => 'scheduled',
            'auto_fill_participants' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'results')
            ->set('feedbackForm.training_session_id', $session->id)
            ->set('feedbackForm.title', 'Soft Skills Survey')
            ->set('feedbackForm.default_question_type', 'multiple_choice')
            ->set('feedbackForm.questions_text', "Question one\nQuestion two")
            ->call('storeFeedbackForm')
            ->assertHasNoErrors();

        $form = TrainingFeedbackForm::query()->where('training_session_id', $session->id)->firstOrFail();

        $this->assertSame([
            ['type' => 'multiple_choice', 'text' => 'Question one'],
            ['type' => 'multiple_choice', 'text' => 'Question two'],
        ], $form->questions);
    }

    public function test_selected_session_participants_can_be_bulk_updated_and_certificate_can_be_downloaded(): void
    {
        Storage::fake('public');

        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create(['id' => 405, 'name' => 'Coordinator']);
        $personnel = $this->createPersonnel($user->id, 405, 'TN-553', 'Sevda');

        $session = TrainingSession::query()->create([
            'title' => 'Coordination Workshop',
            'scheduled_start_at' => '2026-08-01 10:00:00',
            'status' => 'scheduled',
            'auto_fill_participants' => true,
        ]);

        $participant = \App\Models\TrainingSessionParticipant::query()->create([
            'training_session_id' => $session->id,
            'personnel_id' => $personnel->id,
            'attendance_status' => 'planned',
        ]);

        $record = TrainingDeliveryRecord::query()->create([
            'training_session_id' => $session->id,
            'personnel_id' => $personnel->id,
            'result_status' => 'completed',
            'certificate_path' => 'training-certificates/test-certificate.pdf',
            'certificate_name' => 'test-certificate.pdf',
            'completed_at' => now(),
        ]);

        Storage::disk('public')->put('training-certificates/test-certificate.pdf', 'certificate-content');

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->call('selectSessionDetail', $session->id)
            ->set('searchSelectedParticipant', 'TN-553')
            ->assertCount('filteredSelectedParticipants', 1)
            ->set('bulkParticipantIds', [$participant->id])
            ->set('bulkAttendanceStatus', 'attended')
            ->call('applyBulkParticipantStatus')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_session_participants', [
            'id' => $participant->id,
            'attendance_status' => 'attended',
        ]);

        $response = app(Dashboard::class)->downloadDeliveryCertificate($record->id);

        $this->assertNotNull($response);
        $this->assertStringContainsString('test-certificate.pdf', (string) $response->headers->get('content-disposition'));

        Livewire::test(Dashboard::class)
            ->call('deleteDeliveryCertificate', $record->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_delivery_records', [
            'id' => $record->id,
            'certificate_path' => null,
            'certificate_name' => null,
        ]);
        Storage::disk('public')->assertMissing('training-certificates/test-certificate.pdf');
    }

    public function test_approved_plan_item_can_generate_and_create_session_proposal(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->grantTrainingNeedsPermissions($user);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        Position::query()->create(['id' => 406, 'name' => 'Coordinator']);

        $group = TrainingCompetencyGroup::query()->create([
            'name' => 'Coordination',
            'slug' => 'coordination',
            'is_active' => true,
        ]);

        $competency = TrainingCompetency::query()->create([
            'training_competency_group_id' => $group->id,
            'name' => 'Meeting Discipline',
            'slug' => 'meeting-discipline',
            'is_active' => true,
        ]);

        $program = TrainingProgram::query()->create([
            'title' => 'Meeting Mastery',
            'slug' => 'meeting-mastery',
            'delivery_type' => 'internal',
            'duration_hours' => 4,
            'is_active' => true,
        ]);

        $plan = TrainingAnnualPlan::query()->create([
            'title' => '2026 Approved Plan',
            'plan_year' => 2026,
            'status' => 'approved',
        ]);

        $item = TrainingPlanItem::query()->create([
            'training_annual_plan_id' => $plan->id,
            'training_competency_id' => $competency->id,
            'training_program_id' => $program->id,
            'position_id' => 406,
            'priority' => 'high',
            'participant_count' => 6,
            'need_count' => 6,
            'estimated_budget' => 600,
            'source_mix' => 'performance_gap',
            'review_status' => 'approved',
            'suggested_score' => 91.5,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class)
            ->set('activeTab', 'calendar');

        $proposals = $component->instance()->sessionProposals;
        $this->assertCount(1, $proposals);
        $this->assertSame($item->id, $proposals->first()['plan_item_id']);

        $component
            ->call('applySessionProposal', $item->id)
            ->assertHasNoErrors();

        $this->assertSame($plan->id, data_get($component->get('sessionForm'), 'training_annual_plan_id'));
        $this->assertSame($program->id, data_get($component->get('sessionForm'), 'training_program_id'));
        $this->assertSame(6, data_get($component->get('sessionForm'), 'capacity'));

        Livewire::test(Dashboard::class)
            ->set('activeTab', 'calendar')
            ->call('createSessionFromProposal', $item->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_sessions', [
            'training_annual_plan_id' => $plan->id,
            'training_program_id' => $program->id,
            'capacity' => 6,
        ]);
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
