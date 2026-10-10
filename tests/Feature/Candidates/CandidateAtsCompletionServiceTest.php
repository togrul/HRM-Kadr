<?php

namespace Tests\Feature\Candidates;

use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\CandidateInterview;
use App\Models\CandidateOffer;
use App\Models\CandidateSource;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Application\Services\CandidateAtsCompletionService;
use App\Modules\Candidates\Livewire\ApplicationAtsPanel;
use App\Modules\Candidates\Livewire\EditRequisition;
use App\Modules\Candidates\Livewire\RequisitionDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CandidateAtsCompletionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_supports_requisition_approval_interview_scorecard_offer_and_talent_pool(): void
    {
        Carbon::setTestNow('2026-05-09 10:00:00');

        $this->assertTrue(Schema::hasTable('candidate_interviews'));
        $this->assertTrue(Schema::hasTable('candidate_scorecards'));
        $this->assertTrue(Schema::hasTable('candidate_offers'));
        $this->assertTrue(Schema::hasTable('candidate_talent_pool_entries'));
        $this->assertTrue(Schema::hasColumn('job_requisitions', 'approval_status'));

        $actor = User::factory()->create(['name' => 'Recruitment Lead']);
        grantAllStructures($actor);
        $application = $this->makeApplication($actor);
        $service = app(CandidateAtsCompletionService::class);

        $requisition = $application->opening->requisition;
        $service->submitRequisition($requisition, $actor->id, 'Vakansiya təsdiqə göndərildi');
        $service->approveRequisition($requisition->refresh(), $actor->id, 'Təsdiqləndi');

        $this->assertDatabaseHas('job_requisitions', [
            'id' => $requisition->id,
            'status' => 'approved',
            'approval_status' => 'approved',
            'approved_by' => $actor->id,
        ]);

        $interview = $service->scheduleInterview($application, [
            'interviewer_id' => $actor->id,
            'scheduled_at' => '2026-05-10 14:30:00',
            'duration_minutes' => 60,
            'location' => 'HR otağı',
            'created_by' => $actor->id,
        ]);

        $this->assertInstanceOf(CandidateInterview::class, $interview);
        $this->assertSame('scheduled', $interview->status);

        $cancelledInterview = $service->updateInterviewStatus($interview, 'cancelled', $actor->id, 'Vaxt uyğun deyil');

        $this->assertSame('cancelled', $cancelledInterview->status);
        $this->assertDatabaseHas('candidate_stage_events', [
            'candidate_application_id' => $application->id,
            'action' => 'interview_cancelled',
            'actor_id' => $actor->id,
        ]);

        $interview = $service->scheduleInterview($application, [
            'interviewer_id' => $actor->id,
            'scheduled_at' => '2026-05-11 11:00:00',
            'duration_minutes' => 45,
            'location' => 'Online',
            'created_by' => $actor->id,
        ]);

        $completedInterview = $service->submitScorecard($interview, [
            ['criterion' => 'Texniki uyğunluq', 'score' => 90],
            ['criterion' => 'Kommunikasiya', 'score' => 80],
        ], $actor->id, 'Müsahibə tamamlandı');

        $this->assertSame('completed', $completedInterview->status);
        $this->assertSame('85.00', (string) $completedInterview->score);

        $offer = $service->createOffer($application, [
            'salary_amount' => 1500,
            'start_date' => '2026-06-01',
            'expires_at' => '2026-05-20',
            'status' => 'sent',
            'created_by' => $actor->id,
        ]);

        $this->assertInstanceOf(CandidateOffer::class, $offer);
        $service->updateOfferStatus($offer, 'accepted', $actor->id, 'Namizəd təklifi qəbul etdi');

        $entry = $service->addToTalentPool($application, [
            'pool_name' => 'future-leaders',
            'valid_until' => '2027-05-09',
            'created_by' => $actor->id,
        ]);

        $this->assertSame('future-leaders', $entry->pool_name);
        $this->assertDatabaseHas('candidate_stage_events', [
            'candidate_application_id' => $application->id,
            'action' => 'scorecard_submitted',
            'actor_id' => $actor->id,
        ]);
        $this->assertDatabaseHas('candidate_stage_events', [
            'candidate_application_id' => $application->id,
            'action' => 'offer_accepted',
            'actor_id' => $actor->id,
        ]);
    }

    public function test_it_reports_stale_requisitions_for_aging_queue(): void
    {
        Carbon::setTestNow('2026-05-09 10:00:00');

        $actor = User::factory()->create();
        grantAllStructures($actor);
        $application = $this->makeApplication($actor);
        $application->opening->requisition->forceFill([
            'approval_status' => 'pending',
            'created_at' => now()->subDays(20),
        ])->save();

        $payload = app(CandidateAtsCompletionService::class)->requisitionAging(14);

        $this->assertSame(1, $payload['total_open']);
        $this->assertSame(1, $payload['stale']);
        $this->assertTrue($payload['rows']->first()['is_stale']);
    }

    public function test_application_ats_panel_persists_operational_steps(): void
    {
        Carbon::setTestNow('2026-05-09 10:00:00');

        $actor = $this->recruitmentUser();
        $application = $this->makeApplication($actor);

        Livewire::actingAs($actor);

        Livewire::test(ApplicationAtsPanel::class, ['application' => $application])
            ->set('interviewForm.interviewer_id', $actor->id)
            ->set('interviewForm.scheduled_at', '2026-05-10T14:30')
            ->set('interviewForm.duration_minutes', 60)
            ->set('interviewForm.location', 'HR otağı')
            ->call('scheduleInterview')
            ->assertHasNoErrors()
            ->call('cancelInterview', CandidateInterview::query()->where('candidate_application_id', $application->id)->value('id'))
            ->assertHasNoErrors()
            ->set('interviewForm.interviewer_id', $actor->id)
            ->set('interviewForm.scheduled_at', '2026-05-11T11:00')
            ->set('interviewForm.duration_minutes', 45)
            ->set('interviewForm.location', 'Online')
            ->call('scheduleInterview')
            ->assertHasNoErrors()
            ->set('scoreForm.interview_id', CandidateInterview::query()->where('candidate_application_id', $application->id)->where('status', 'scheduled')->latest('id')->value('id'))
            ->set('scoreForm.technical', 90)
            ->set('scoreForm.communication', 80)
            ->set('scoreForm.culture', 85)
            ->call('submitScorecard')
            ->assertHasNoErrors()
            ->set('offerForm.salary_amount', 1500)
            ->set('offerForm.currency', 'AZN')
            ->set('offerForm.start_date', '2026-06-01')
            ->set('offerForm.expires_at', '2026-05-20')
            ->call('createOffer')
            ->assertHasNoErrors()
            ->call('updateOfferStatus', CandidateOffer::query()->where('candidate_application_id', $application->id)->value('id'), 'accepted')
            ->set('poolForm.pool_name', 'future-leaders')
            ->set('poolForm.valid_until', '2027-05-09')
            ->call('addToTalentPool')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('candidate_interviews', [
            'candidate_application_id' => $application->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('candidate_offers', [
            'candidate_application_id' => $application->id,
            'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('candidate_talent_pool_entries', [
            'candidate_application_id' => $application->id,
            'pool_name' => 'future-leaders',
        ]);
    }

    public function test_requisition_detail_controls_approval_flow(): void
    {
        Carbon::setTestNow('2026-05-09 10:00:00');

        $actor = $this->recruitmentUser();
        $application = $this->makeApplication($actor);
        $requisition = $application->opening->requisition;

        Livewire::actingAs($actor);

        Livewire::test(RequisitionDetail::class, ['requisition' => $requisition])
            ->set('approvalNote', 'Təsdiqə göndərildi')
            ->call('submitForApproval')
            ->assertHasNoErrors()
            ->set('approvalNote', 'Ştat uyğunluğu təsdiqləndi')
            ->call('approve')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('job_requisitions', [
            'id' => $requisition->id,
            'approval_status' => 'approved',
            'approved_by' => $actor->id,
            'approval_note' => 'Ştat uyğunluğu təsdiqləndi',
        ]);
    }

    public function test_application_ats_panel_shows_one_form_per_tab_and_toasts_saves(): void
    {
        Carbon::setTestNow('2026-05-09 10:00:00');

        $actor = $this->recruitmentUser();
        $application = $this->makeApplication($actor);

        Livewire::actingAs($actor);

        Livewire::test(ApplicationAtsPanel::class, ['application' => $application])
            ->assertSeeHtml('wire:submit="scheduleInterview"')
            ->assertDontSeeHtml('wire:submit="createOffer"')
            ->call('setTab', 'offers')
            ->assertSeeHtml('wire:submit="createOffer"')
            ->assertDontSeeHtml('wire:submit="scheduleInterview"')
            ->call('setTab', 'bogus')
            ->assertSet('tab', 'interviews')
            ->set('interviewForm.scheduled_at', '2026-05-10T14:30')
            ->call('scheduleInterview')
            ->assertHasNoErrors()
            ->assertDispatched('notify', type: 'success', message: __('candidates::recruitment.messages.interview_scheduled'));
    }

    public function test_requisition_reject_is_confirmed_and_toasted(): void
    {
        Carbon::setTestNow('2026-05-09 10:00:00');

        $actor = $this->recruitmentUser();
        $requisition = $this->makeApplication($actor)->opening->requisition;

        Livewire::actingAs($actor);

        app(CandidateAtsCompletionService::class)->submitRequisition($requisition, $actor->id);

        Livewire::test(RequisitionDetail::class, ['requisition' => $requisition->refresh()])
            ->assertSeeHtml("\$dispatch('confirm-action'")
            ->assertSee(__('candidates::recruitment.messages.reject_requisition_confirm', ['title' => $requisition->title]))
            ->assertSeeHtml('run: () => $wire.reject()')
            ->call('reject')
            ->assertDispatched('notify', type: 'success', message: __('candidates::recruitment.messages.requisition_rejected'));

        $this->assertDatabaseHas('job_requisitions', ['id' => $requisition->id, 'approval_status' => 'rejected']);
    }

    public function test_requisition_cannot_be_approved_or_rejected_before_it_is_submitted(): void
    {
        $actor = $this->recruitmentUser();
        $requisition = $this->makeApplication($actor)->opening->requisition;

        Livewire::actingAs($actor);

        Livewire::test(RequisitionDetail::class, ['requisition' => $requisition])
            ->assertDontSee(__('candidates::recruitment.actions.approve_requisition'))
            ->call('approve')
            ->assertHasErrors(['approvalNote'])
            ->call('reject')
            ->assertHasErrors(['approvalNote']);

        $this->assertDatabaseMissing('job_requisitions', ['id' => $requisition->id, 'approval_status' => 'approved']);
        $this->assertDatabaseMissing('job_requisitions', ['id' => $requisition->id, 'approval_status' => 'rejected']);
    }

    public function test_requisition_approval_controls_are_hidden_and_refused_without_edit_permission(): void
    {
        $actor = $this->recruitmentUser();
        $requisition = $this->makeApplication($actor)->opening->requisition;
        app(CandidateAtsCompletionService::class)->submitRequisition($requisition, $actor->id);

        Permission::findOrCreate('show-candidates', 'web');
        $viewer = User::factory()->create(['is_active' => true]);
        grantAllStructures($viewer);
        $viewer->givePermissionTo('show-candidates');

        Livewire::actingAs($viewer);

        Livewire::test(RequisitionDetail::class, ['requisition' => $requisition->refresh()])
            ->assertDontSee(__('candidates::recruitment.actions.approve_requisition'))
            ->assertDontSee(__('candidates::recruitment.actions.submit_for_approval'))
            ->call('approve')
            ->assertForbidden();
    }

    public function test_requisition_edit_form_keeps_its_approval_status_but_cannot_pick_one(): void
    {
        $actor = $this->recruitmentUser();
        $requisition = $this->makeApplication($actor)->opening->requisition;

        Livewire::actingAs($actor);

        Livewire::test(EditRequisition::class, ['requisitionModel' => $requisition->id])
            ->set('form.status', 'approved')
            ->call('store')
            ->assertHasErrors(['form.status']);

        app(CandidateAtsCompletionService::class)->submitRequisition($requisition, $actor->id);

        Livewire::test(EditRequisition::class, ['requisitionModel' => $requisition->id])
            ->assertSet('form.status', 'pending_approval')
            ->call('store')
            ->assertHasNoErrors(['form.status']);
    }

    public function test_scorecard_cannot_target_a_cancelled_interview(): void
    {
        $actor = $this->recruitmentUser();
        $application = $this->makeApplication($actor);
        $interview = CandidateInterview::query()->create([
            'candidate_application_id' => $application->id,
            'stage_key' => 'interview',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 45,
            'status' => 'cancelled',
        ]);

        Livewire::actingAs($actor);

        Livewire::test(ApplicationAtsPanel::class, ['application' => $application])
            ->call('setTab', 'scorecard')
            ->assertDontSeeHtml('<option value="'.$interview->id.'"')
            ->set('scoreForm.interview_id', $interview->id)
            ->call('submitScorecard')
            ->assertHasErrors(['scoreForm.interview_id']);

        $this->assertSame('cancelled', $interview->refresh()->status);
    }

    public function test_offer_status_changes_ask_for_confirmation(): void
    {
        $actor = $this->recruitmentUser();
        $application = $this->makeApplication($actor);
        app(CandidateAtsCompletionService::class)->createOffer($application, ['status' => 'sent', 'created_by' => $actor->id]);

        Livewire::actingAs($actor);

        Livewire::test(ApplicationAtsPanel::class, ['application' => $application])
            ->call('setTab', 'offers')
            ->assertDontSeeHtml('wire:click="updateOfferStatus(')
            ->assertSeeHtml('$wire.updateOfferStatus(');
    }

    private function makeApplication(User $actor): CandidateApplication
    {
        $structure = Structure::query()->create(['name' => 'ATS HQ', 'shortname' => 'ATS']);
        $position = Position::query()->create(['name' => 'Backend Engineer']);
        $status = AppealStatus::query()->create(['name' => 'Yeni', 'locale' => app()->getLocale()]);
        $source = CandidateSource::query()->create([
            'name' => 'Career Site',
            'slug' => 'career-site',
            'channel' => 'digital',
            'creator_id' => $actor->id,
        ]);
        $candidate = Candidate::query()->create([
            'surname' => 'Namizəd',
            'name' => 'Test',
            'patronymic' => 'ATS',
            'structure_id' => $structure->id,
            'status_id' => $status->id,
            'height' => 180,
            'creator_id' => $actor->id,
        ]);
        $requisition = JobRequisition::query()->create([
            'title' => 'Backend Engineer',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => 'private',
            'requested_by' => $actor->id,
            'owner_id' => $actor->id,
        ]);
        $opening = JobOpening::query()->create([
            'job_requisition_id' => $requisition->id,
            'title' => 'Backend Engineer',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => 'private',
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
        ]);

        return CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'candidate_source_id' => $source->id,
            'assigned_recruiter_id' => $actor->id,
            'current_stage' => 'interview',
            'status' => 'active',
        ]);
    }

    private function recruitmentUser(): User
    {
        foreach (['show-candidates', 'edit-candidates'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['name' => 'Recruitment Lead', 'is_active' => true]);
        grantAllStructures($user);
        $user->givePermissionTo(['show-candidates', 'edit-candidates']);

        return $user;
    }
}
