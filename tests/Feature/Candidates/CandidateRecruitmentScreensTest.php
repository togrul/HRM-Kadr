<?php

namespace Tests\Feature\Candidates;

use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\CandidateSource;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Livewire\ApplicationPipeline;
use App\Modules\Candidates\Livewire\ApplicationStageActionPanel;
use App\Modules\Candidates\Livewire\AddApplication;
use App\Modules\Candidates\Livewire\ApplicationDetail;
use App\Modules\Candidates\Livewire\AddOpening;
use App\Modules\Candidates\Livewire\AddRequisition;
use App\Modules\Candidates\Livewire\EditCandidate;
use App\Modules\Candidates\Livewire\RecruitmentAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CandidateRecruitmentScreensTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('candidates.workflow_visible_packs', [
            'military',
            'public',
            'private',
        ]);
    }

    public function test_recruitment_routes_render_for_authorized_user(): void
    {
        [$user, $requisition, $opening] = $this->seedRecruitmentData();

        $this->actingAs($user)
            ->get(route('candidates.requisitions'))
            ->assertOk()
            ->assertSee(__('candidates::recruitment.titles.requisitions'));

        $this->actingAs($user)
            ->get(route('candidates.requisitions.show', $requisition))
            ->assertOk()
            ->assertSee($requisition->title);

        $this->actingAs($user)
            ->get(route('candidates.openings'))
            ->assertOk()
            ->assertSee(__('candidates::recruitment.titles.openings'));

        $this->actingAs($user)
            ->get(route('candidates.openings.show', $opening))
            ->assertOk()
            ->assertSee($opening->title);

        $this->actingAs($user)
            ->get(route('candidates.applications'))
            ->assertOk()
            ->assertSee(__('candidates::recruitment.titles.pipeline'));

        $this->actingAs($user)
            ->get(route('candidates.analytics'))
            ->assertOk()
            ->assertSee(__('candidates::recruitment.titles.analytics'));
    }

    public function test_opening_detail_shows_application_actions(): void
    {
        [$user, , $opening] = $this->seedRecruitmentData();

        $this->actingAs($user)
            ->get(route('candidates.openings.show', $opening))
            ->assertOk()
            ->assertSee(__('candidates::recruitment.actions.add_application'))
            ->assertSee(__('candidates::recruitment.actions.open_pipeline'));
    }

    public function test_add_requisition_component_can_store_record(): void
    {
        [$user, , , $structure, $position] = $this->seedRecruitmentData();

        Livewire::actingAs($user)
            ->test(AddRequisition::class)
            ->set('form.title', 'Yeni data engineer')
            ->set('form.structure_id', $structure->id)
            ->set('form.position_id', $position->id)
            ->set('form.profile_pack', 'private')
            ->set('form.employment_type', 'full_time')
            ->set('form.hiring_reason', 'Growth')
            ->set('form.headcount', 2)
            ->set('form.status', 'open')
            ->call('store')
            ->assertDispatched('requisitionSaved');

        $this->assertDatabaseHas('job_requisitions', [
            'title' => 'Yeni data engineer',
            'profile_pack' => 'private',
            'headcount' => 2,
        ]);
    }

    public function test_add_opening_component_can_store_record(): void
    {
        [$user, $requisition, , $structure, $position] = $this->seedRecruitmentData();

        Livewire::actingAs($user)
            ->test(AddOpening::class)
            ->set('form.job_requisition_id', $requisition->id)
            ->set('form.title', 'Data engineer opening')
            ->set('form.structure_id', $structure->id)
            ->set('form.position_id', $position->id)
            ->set('form.profile_pack', 'public')
            ->set('form.opening_type', 'standard')
            ->set('form.headcount', 1)
            ->set('form.status', 'open')
            ->call('store')
            ->assertDispatched('openingSaved');

        $this->assertDatabaseHas('job_openings', [
            'title' => 'Data engineer opening',
            'job_requisition_id' => $requisition->id,
            'profile_pack' => 'public',
        ]);
    }

    public function test_add_application_component_can_store_record(): void
    {
        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();
        $source = CandidateSource::query()->create([
            'name' => 'Referral',
            'slug' => 'referral',
            'channel' => 'internal',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(AddApplication::class, ['openingModel' => $opening->id])
            ->set('form.job_opening_id', $opening->id)
            ->set('form.candidate_id', $candidate->id)
            ->set('form.candidate_source_id', $source->id)
            ->set('form.assigned_recruiter_id', $user->id)
            ->set('form.applied_at', '2026-03-30')
            ->set('form.note', 'Initial review completed')
            ->call('store')
            ->assertDispatched('applicationSaved');

        $this->assertDatabaseHas('candidate_applications', [
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'candidate_source_id' => $source->id,
            'assigned_recruiter_id' => $user->id,
            'current_stage' => 'applied',
            'status' => 'active',
        ]);

        $application = CandidateApplication::query()->firstOrFail();

        $this->assertDatabaseHas('candidate_stage_events', [
            'candidate_application_id' => $application->id,
            'stage_key' => 'applied',
            'action' => 'created',
        ]);
    }

    public function test_application_detail_route_and_stage_transition_work(): void
    {
        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'applied',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('candidates.applications.show', $application))
            ->assertOk()
            ->assertSee(__('candidates::recruitment.titles.application_detail'));

        Livewire::actingAs($user)
            ->test(ApplicationStageActionPanel::class, ['applicationId' => $application->id])
            ->set('form.to_stage', 'screening')
            ->set('form.occurred_at', '2026-03-30')
            ->set('form.note', 'Initial screening passed')
            ->call('applyStageTransition')
            ->assertDispatched('applicationSaved');

        $this->assertDatabaseHas('candidate_applications', [
            'id' => $application->id,
            'current_stage' => 'screening',
        ]);

        $this->assertDatabaseHas('candidate_stage_events', [
            'candidate_application_id' => $application->id,
            'stage_key' => 'screening',
            'action' => 'moved',
        ]);
    }

    public function test_application_detail_persists_stage_specific_assessment_and_document_records(): void
    {
        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'applied',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(ApplicationStageActionPanel::class, ['applicationId' => $application->id])
            ->set('form.to_stage', 'screening')
            ->set('form.occurred_at', '2026-03-30')
            ->set('form.score', 88)
            ->set('form.decision', 'passed')
            ->set('form.assessment_items.cv_match.status', 'passed')
            ->set('form.assessment_items.cv_match.note', 'Profile aligns well')
            ->set('form.assessment_items.salary_expectation.status', 'pending')
            ->set('form.document_items.cv.is_provided', true)
            ->set('form.document_items.cv.note', 'Uploaded by recruiter')
            ->set('form.profile_fields.salary_expectation', 5200)
            ->set('form.profile_fields.notice_period_days', 30)
            ->call('applyStageTransition')
            ->assertDispatched('applicationSaved');

        $this->assertDatabaseHas('candidate_application_assessments', [
            'candidate_application_id' => $application->id,
            'stage_key' => 'screening',
            'assessment_key' => 'cv_match',
            'status' => 'passed',
            'note' => 'Profile aligns well',
        ]);

        $this->assertDatabaseHas('candidate_application_document_checks', [
            'candidate_application_id' => $application->id,
            'stage_key' => 'screening',
            'document_key' => 'cv',
            'is_provided' => 1,
            'note' => 'Uploaded by recruiter',
        ]);

        $profile = $application->fresh()->stageProfiles()->firstWhere('stage_key', 'screening');

        $this->assertNotNull($profile);
        $this->assertSame(5200, $profile->payload['salary_expectation'] ?? null);
        $this->assertSame(30, $profile->payload['notice_period_days'] ?? null);
    }

    public function test_application_detail_uploads_stage_documents_into_document_checks(): void
    {
        Storage::fake('local');

        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'applied',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(ApplicationStageActionPanel::class, ['applicationId' => $application->id])
            ->set('form.to_stage', 'screening')
            ->set('form.document_items.cv.note', 'Checklist file uploaded')
            ->set('uploadedDocumentFiles.cv', [
                UploadedFile::fake()->create('cv-checklist.pdf', 48, 'application/pdf'),
            ])
            ->call('uploadStageDocument', 'cv');

        $this->assertDatabaseHas('candidate_documents', [
            'candidate_id' => $candidate->id,
            'candidate_application_id' => $application->id,
            'stage_key' => 'screening',
            'document_key' => 'cv',
            'display_name' => 'cv-checklist.pdf',
        ]);

        $this->assertDatabaseHas('candidate_application_document_checks', [
            'candidate_application_id' => $application->id,
            'stage_key' => 'screening',
            'document_key' => 'cv',
            'is_provided' => 1,
            'note' => 'Checklist file uploaded',
        ]);
    }

    public function test_application_detail_requires_transition_permission_for_non_terminal_stage_move(): void
    {
        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'applied',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

        Livewire::actingAs($viewer)
            ->test(ApplicationStageActionPanel::class, ['applicationId' => $application->id])
            ->set('form.to_stage', 'screening')
            ->set('form.occurred_at', '2026-03-30')
            ->call('applyStageTransition')
            ->assertForbidden();

        $recruiter = User::factory()->create();
        $recruiter->givePermissionTo([
            Permission::findOrCreate('show-candidates', 'web'),
            Permission::findOrCreate('candidate-applications.transition', 'web'),
        ]);

        Livewire::actingAs($recruiter)
            ->test(ApplicationStageActionPanel::class, ['applicationId' => $application->id])
            ->set('form.to_stage', 'screening')
            ->set('form.occurred_at', '2026-03-30')
            ->call('applyStageTransition')
            ->assertDispatched('applicationSaved');
    }

    public function test_application_detail_requires_appoint_permission_for_final_stage(): void
    {
        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'offer',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        $recruiter = User::factory()->create();
        $recruiter->givePermissionTo([
            Permission::findOrCreate('show-candidates', 'web'),
            Permission::findOrCreate('candidate-applications.transition', 'web'),
        ]);

        Livewire::actingAs($recruiter)
            ->test(ApplicationStageActionPanel::class, ['applicationId' => $application->id])
            ->set('form.to_stage', 'hired')
            ->set('form.occurred_at', '2026-03-30')
            ->set('form.final_decision', 'hired')
            ->call('applyStageTransition')
            ->assertForbidden();

        $approver = User::factory()->create();
        $approver->givePermissionTo([
            Permission::findOrCreate('show-candidates', 'web'),
            Permission::findOrCreate('candidate-applications.appoint', 'web'),
        ]);

        Livewire::actingAs($approver)
            ->test(ApplicationStageActionPanel::class, ['applicationId' => $application->id])
            ->set('form.to_stage', 'hired')
            ->set('form.occurred_at', '2026-03-30')
            ->set('form.final_decision', 'hired')
            ->call('applyStageTransition')
            ->assertDispatched('applicationSaved');
    }

    public function test_application_detail_requires_reject_permission_for_rejection_stage(): void
    {
        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'screening',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

        Livewire::actingAs($reviewer)
            ->test(ApplicationStageActionPanel::class, ['applicationId' => $application->id])
            ->set('form.to_stage', 'rejected')
            ->set('form.occurred_at', '2026-03-30')
            ->set('form.final_decision', 'rejected')
            ->call('applyStageTransition')
            ->assertForbidden();

        $rejector = User::factory()->create();
        $rejector->givePermissionTo([
            Permission::findOrCreate('show-candidates', 'web'),
            Permission::findOrCreate('candidate-applications.reject', 'web'),
        ]);

        Livewire::actingAs($rejector)
            ->test(ApplicationStageActionPanel::class, ['applicationId' => $application->id])
            ->set('form.to_stage', 'rejected')
            ->set('form.occurred_at', '2026-03-30')
            ->set('form.final_decision', 'rejected')
            ->call('applyStageTransition')
            ->assertDispatched('applicationSaved');
    }

    public function test_candidate_list_shows_deep_links_to_recruitment_context(): void
    {
        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'applied',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('candidates'))
            ->assertOk()
            ->assertSee(route('candidates.applications.show', $application), false)
            ->assertSee(route('candidates.openings.show', $opening), false)
            ->assertSee(route('candidates.applications', ['candidate' => $candidate->id]), false);
    }

    public function test_application_pipeline_uses_single_aggregate_query_for_summary_metrics(): void
    {
        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'applied',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($user)
            ->test(ApplicationPipeline::class)
            ->assertSee(__('candidates::recruitment.titles.pipeline'));

        $queries = collect(DB::getQueryLog())
            ->pluck('query')
            ->map(fn ($query) => strtolower($query));

        $metricQueries = $queries->filter(fn ($query) => str_contains($query, 'count(distinct job_opening_id) as total_openings'));

        $this->assertCount(1, $metricQueries);
    }

    public function test_pipeline_stage_summary_respects_locked_private_pack(): void
    {
        config()->set('candidates.workflow_pack', 'private');
        config()->set('candidates.workflow_visible_packs', 'auto');

        [$user] = $this->seedRecruitmentData();

        $this->actingAs($user)
            ->get(route('candidates.applications', ['pack' => 'all']))
            ->assertOk()
            ->assertSee(__('candidates::recruitment.stages.interview'))
            ->assertDontSee(__('candidates::recruitment.stages.physical_test'));
    }

    public function test_legacy_candidate_edit_screen_uses_public_workflow_pack_when_mode_is_auto(): void
    {
        config()->set('candidates.mode', 'military');
        config()->set('candidates.workflow_pack', 'public');

        [$user] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(EditCandidate::class, ['candidateModel' => $candidate->id])
            ->assertSee('Public')
            ->assertDontSee(__('candidates::common.labels.height'))
            ->assertDontSee(__('candidates::common.labels.knowledge_test'))
            ->assertDontSee(__('candidates::common.labels.military_service'))
            ->assertDontSee(__('candidates::common.labels.hhk_result'))
            ->assertDontSee(__('candidates::common.labels.attitude_to_military'));
    }

    public function test_recruitment_analytics_component_renders_private_pack_metrics(): void
    {
        config()->set('candidates.workflow_pack', 'private');
        config()->set('candidates.workflow_visible_packs', 'auto');

        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'interview',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(RecruitmentAnalytics::class)
            ->assertSee(__('candidates::recruitment.titles.analytics'))
            ->assertSee(__('candidates::recruitment.titles.time_to_stage'))
            ->assertSee(__('candidates::recruitment.titles.source_effectiveness'))
            ->assertSee(__('candidates::recruitment.titles.rejection_reasons'))
            ->assertSee(__('candidates::recruitment.labels.requisitions'))
            ->assertSee(__('candidates::recruitment.labels.openings'))
            ->assertSee(__('candidates::recruitment.stages.interview'))
            ->assertDontSee('candidates::recruitment.labels.requisitions')
            ->assertDontSee('candidates::recruitment.labels.openings')
            ->assertDontSee(__('candidates::recruitment.stages.physical_test'));
    }

    public function test_legacy_candidate_header_shows_latest_application_context(): void
    {
        config()->set('candidates.workflow_pack', 'private');

        [$user, , $opening] = $this->seedRecruitmentData();
        $candidate = Candidate::query()->firstOrFail();

        CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'screening',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(EditCandidate::class, ['candidateModel' => $candidate->id])
            ->assertSee(__('candidates::recruitment.labels.total_applications'))
            ->assertSee(__('candidates::recruitment.labels.latest_opening'))
            ->assertSee(__('candidates::recruitment.titles.recent_applications'))
            ->assertSee($opening->title);
    }

    private function seedRecruitmentData(): array
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('show-candidates', 'web'),
            Permission::findOrCreate('add-candidates', 'web'),
            Permission::findOrCreate('edit-candidates', 'web'),
        ]);

        $structure = Structure::query()->create([
            'name' => 'Analytics Department',
            'shortname' => 'AD',
        ]);

        $position = Position::query()->create([
            'name' => 'Data Engineer',
            'approval_rank' => 0,
            'is_approval_target' => false,
        ]);

        $status = AppealStatus::query()->create([
            'name' => 'Yeni',
            'locale' => app()->getLocale(),
        ]);

        Candidate::query()->create([
            'surname' => 'Test',
            'name' => 'Namizəd',
            'patronymic' => 'One',
            'structure_id' => $structure->id,
            'status_id' => $status->id,
            'height' => 180,
            'creator_id' => $user->id,
        ]);

        $requisition = JobRequisition::query()->create([
            'title' => 'Data Engineer Requisition',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => 'private',
            'requested_by' => $user->id,
            'owner_id' => $user->id,
            'headcount' => 1,
            'status' => 'open',
        ]);

        $opening = JobOpening::query()->create([
            'job_requisition_id' => $requisition->id,
            'title' => 'Data Engineer Opening',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => 'private',
            'headcount' => 1,
            'status' => 'open',
            'owner_id' => $user->id,
            'created_by' => $user->id,
        ]);

        return [$user, $requisition, $opening, $structure, $position];
    }
}
