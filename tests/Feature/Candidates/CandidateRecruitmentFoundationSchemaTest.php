<?php

namespace Tests\Feature\Candidates;

use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\CandidateRejectionReason;
use App\Models\CandidateSource;
use App\Models\CandidateStageEvent;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CandidateRecruitmentFoundationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_a_recruitment_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('candidate_sources'));
        $this->assertTrue(Schema::hasTable('candidate_rejection_reasons'));
        $this->assertTrue(Schema::hasTable('job_requisitions'));
        $this->assertTrue(Schema::hasTable('job_openings'));
        $this->assertTrue(Schema::hasTable('candidate_applications'));
        $this->assertTrue(Schema::hasTable('candidate_stage_events'));
    }

    public function test_foundation_entities_can_be_connected_without_breaking_candidate_master(): void
    {
        $structure = Structure::query()->create([
            'name' => 'Test Structure',
            'shortname' => 'TS',
        ]);

        $position = Position::query()->create([
            'name' => 'Developer',
            'approval_rank' => 0,
            'is_approval_target' => false,
        ]);

        $user = User::factory()->create();
        $status = AppealStatus::query()->create([
            'name' => 'Yeni',
            'locale' => app()->getLocale(),
        ]);

        $candidate = Candidate::query()->create([
            'surname' => 'Quliyev',
            'name' => 'Aydın',
            'patronymic' => 'Test',
            'structure_id' => $structure->id,
            'status_id' => $status->id,
            'height' => 180,
            'creator_id' => $user->id,
        ]);

        $source = CandidateSource::query()->create([
            'name' => 'Career Site',
            'slug' => 'career-site',
            'channel' => 'digital',
            'creator_id' => $user->id,
        ]);

        $reason = CandidateRejectionReason::query()->create([
            'name' => 'Role mismatch',
            'slug' => 'role-mismatch',
            'profile_pack' => 'private',
        ]);

        $requisition = JobRequisition::query()->create([
            'title' => 'Backend Engineer',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => 'private',
            'requested_by' => $user->id,
            'owner_id' => $user->id,
        ]);

        $opening = JobOpening::query()->create([
            'job_requisition_id' => $requisition->id,
            'title' => 'Backend Engineer',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => 'private',
            'owner_id' => $user->id,
            'created_by' => $user->id,
        ]);

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'candidate_source_id' => $source->id,
            'assigned_recruiter_id' => $user->id,
            'current_stage' => 'screening',
            'status' => 'active',
        ]);

        $event = CandidateStageEvent::query()->create([
            'candidate_application_id' => $application->id,
            'stage_key' => 'screening',
            'action' => 'advance',
            'decision' => 'passed',
            'actor_id' => $user->id,
            'score' => 87.50,
            'payload' => ['source' => 'initial_review'],
        ]);

        $this->assertSame($requisition->id, $opening->requisition->id);
        $this->assertSame($opening->id, $application->opening->id);
        $this->assertSame($candidate->id, $application->candidate->id);
        $this->assertSame($source->id, $application->source->id);
        $this->assertSame($event->id, $application->stageEvents->first()->id);

        $application->update([
            'rejection_reason_id' => $reason->id,
            'status' => 'rejected',
        ]);

        $this->assertSame($reason->id, $application->fresh()->rejectionReason->id);
    }
}
