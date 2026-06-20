<?php

namespace Tests\Unit\Modules\Candidates;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageArtifactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateApplicationStageArtifactServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_and_hydrates_stage_artifacts(): void
    {
        $application = $this->seedApplication();
        $service = app(CandidateApplicationStageArtifactService::class);

        $service->syncForStage($application, 'screening', [
            'cv_match' => ['status' => 'passed', 'note' => 'Strong fit'],
            'salary_expectation' => ['status' => 'pending', 'note' => ''],
        ], [
            'cv' => ['is_provided' => true, 'note' => 'Uploaded'],
            'portfolio' => ['is_provided' => false, 'note' => 'Not required'],
        ], actorId: 1, recordedAt: now());

        $application->refresh()->load(['assessments', 'documentChecks']);

        $hydrated = $service->hydrateStageFormState(
            $application,
            'screening',
            ['cv_match', 'salary_expectation'],
            ['cv', 'portfolio'],
        );

        $this->assertSame('passed', $hydrated['assessment_items']['cv_match']['status']);
        $this->assertSame('Strong fit', $hydrated['assessment_items']['cv_match']['note']);
        $this->assertTrue($hydrated['document_items']['cv']['is_provided']);
        $this->assertSame('Uploaded', $hydrated['document_items']['cv']['note']);
    }

    private function seedApplication(): CandidateApplication
    {
        $user = User::factory()->create();
        $structure = Structure::query()->create(['name' => 'Analytics', 'shortname' => 'AN']);
        $position = Position::query()->create(['name' => 'Engineer', 'approval_rank' => 0, 'is_approval_target' => false]);

        $candidate = Candidate::query()->create([
            'surname' => 'Test',
            'name' => 'Candidate',
            'patronymic' => 'One',
            'structure_id' => $structure->id,
            'height' => 180,
            'creator_id' => $user->id,
        ]);

        $requisition = JobRequisition::query()->create([
            'title' => 'Req',
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
            'title' => 'Opening',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => 'private',
            'headcount' => 1,
            'status' => 'open',
            'owner_id' => $user->id,
            'created_by' => $user->id,
        ]);

        return CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'applied',
            'status' => 'active',
            'applied_at' => now(),
            'moved_at' => now(),
        ]);
    }
}
