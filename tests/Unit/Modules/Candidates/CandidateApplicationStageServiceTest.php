<?php

namespace Tests\Unit\Modules\Candidates;

use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateApplicationStageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_initial_application_for_opening(): void
    {
        [$candidate, $opening, $user] = $this->makeCandidateAndOpening('private');

        $application = app(CandidateApplicationStageService::class)->createInitialApplication($candidate, $opening, [
            'assigned_recruiter_id' => $user->id,
            'actor_id' => $user->id,
        ]);

        $this->assertSame($candidate->id, $application->candidate_id);
        $this->assertSame($opening->id, $application->job_opening_id);
        $this->assertSame('applied', $application->current_stage);
        $this->assertSame('active', $application->status);
        $this->assertDatabaseHas('candidate_stage_events', [
            'candidate_application_id' => $application->id,
            'stage_key' => 'applied',
            'action' => 'created',
        ]);
    }

    public function test_it_moves_application_to_terminal_stage_and_builds_summary(): void
    {
        [$candidate, $opening, $user] = $this->makeCandidateAndOpening('public');

        /** @var CandidateApplication $application */
        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'exam',
            'status' => 'active',
            'assigned_recruiter_id' => $user->id,
            'applied_at' => now(),
            'moved_at' => now(),
        ]);

        $service = app(CandidateApplicationStageService::class);
        $updated = $service->moveToStage($application, 'appointed', [
            'actor_id' => $user->id,
            'action' => 'decision',
        ]);

        $this->assertSame('appointed', $updated->current_stage);
        $this->assertSame('closed', $updated->status);
        $this->assertSame('appointed', $updated->final_decision);

        $summary = collect($service->stageSummaryForOpening($opening))->keyBy('key');

        $this->assertSame(1, $summary['appointed']['count']);
        $this->assertTrue($summary['appointed']['terminal']);
    }

    public function test_it_persists_audit_summary_payload_for_stage_moves(): void
    {
        [$candidate, $opening, $user] = $this->makeCandidateAndOpening('private');

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'applied',
            'status' => 'active',
            'assigned_recruiter_id' => $user->id,
            'applied_at' => now()->subDays(2),
            'moved_at' => now()->subDay(),
        ]);

        app(CandidateApplicationStageService::class)->moveToStage($application, 'screening', [
            'actor_id' => $user->id,
            'assessment_items' => [
                'cv_match' => ['status' => 'passed', 'note' => 'Strong fit'],
                'salary_expectation' => ['status' => 'failed', 'note' => 'Above budget'],
            ],
            'document_items' => [
                'cv' => ['is_provided' => true, 'note' => 'Uploaded'],
                'portfolio' => ['is_provided' => false, 'note' => null],
            ],
            'profile_fields' => [
                'salary_expectation' => 5400,
                'notice_period_days' => 21,
            ],
        ]);

        $payload = $application->fresh()->stageEvents()->latest('id')->value('payload');

        $this->assertSame('applied', data_get($payload, 'audit.from_stage'));
        $this->assertSame('screening', data_get($payload, 'audit.to_stage'));
        $this->assertSame(2, data_get($payload, 'audit.assessment_total'));
        $this->assertSame(1, data_get($payload, 'audit.assessment_passed'));
        $this->assertSame(1, data_get($payload, 'audit.assessment_failed'));
        $this->assertSame(2, data_get($payload, 'audit.document_total'));
        $this->assertSame(1, data_get($payload, 'audit.document_provided'));
        $this->assertSame(['salary_expectation', 'notice_period_days'], data_get($payload, 'audit.profile_field_keys'));
    }

    private function makeCandidateAndOpening(string $pack): array
    {
        $structure = Structure::query()->create([
            'name' => 'Recruitment Structure '.$pack,
            'shortname' => strtoupper(substr($pack, 0, 2)),
        ]);

        $position = Position::query()->create([
            'name' => 'Engineer '.$pack,
            'approval_rank' => 0,
            'is_approval_target' => false,
        ]);

        $user = User::factory()->create();

        $status = AppealStatus::query()->create([
            'name' => 'Yeni '.$pack,
            'locale' => app()->getLocale(),
        ]);

        $candidate = Candidate::query()->create([
            'surname' => 'Əliyev',
            'name' => 'Murad',
            'patronymic' => 'Test',
            'structure_id' => $structure->id,
            'status_id' => $status->id,
            'height' => 180,
            'creator_id' => $user->id,
        ]);

        $opening = JobOpening::query()->create([
            'title' => 'Opening '.$pack,
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => $pack,
            'status' => 'open',
            'owner_id' => $user->id,
            'created_by' => $user->id,
        ]);

        return [$candidate, $opening, $user];
    }
}
