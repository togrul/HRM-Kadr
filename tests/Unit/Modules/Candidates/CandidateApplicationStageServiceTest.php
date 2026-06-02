<?php

namespace Tests\Unit\Modules\Candidates;

use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Application\Services\CandidateApplicationStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
        $this->assertNotNull($updated->personnel_id);

        $personnel = Personnel::query()->findOrFail($updated->personnel_id);

        $this->assertSame('Əliyev Murad Test', $personnel->fullname);
        $this->assertSame($opening->structure_id, $personnel->structure_id);
        $this->assertSame($opening->position_id, $personnel->position_id);

        $this->assertDatabaseHas('employee_lifecycle_events', [
            'personnel_id' => $personnel->id,
            'source_type' => 'candidate_application',
            'source_id' => $updated->id,
            'type' => 'onboarding',
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('candidate_stage_events', [
            'candidate_application_id' => $updated->id,
            'action' => 'converted_to_personnel',
        ]);

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

    public function test_terminal_stage_conversion_is_idempotent(): void
    {
        [$candidate, $opening, $user] = $this->makeCandidateAndOpening('private');

        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'offer',
            'status' => 'active',
            'assigned_recruiter_id' => $user->id,
            'applied_at' => now()->subDays(5),
            'moved_at' => now()->subDay(),
        ]);

        $service = app(CandidateApplicationStageService::class);

        $first = $service->moveToStage($application, 'hired', [
            'actor_id' => $user->id,
            'action' => 'decision',
        ]);
        $second = $service->moveToStage($first->fresh(), 'hired', [
            'actor_id' => $user->id,
            'action' => 'decision',
        ]);

        $this->assertSame($first->personnel_id, $second->personnel_id);
        $this->assertSame(1, Personnel::query()->count());
        $this->assertSame(1, DB::table('employee_lifecycle_events')
            ->where('source_type', 'candidate_application')
            ->where('source_id', $application->id)
            ->count());
    }

    public function test_approved_requisition_hire_maps_candidate_to_personnel_once(): void
    {
        [$candidate, $opening, $user] = $this->makeCandidateAndOpening('private', true);

        /** @var CandidateApplication $application */
        $application = CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'current_stage' => 'offer',
            'status' => 'active',
            'assigned_recruiter_id' => $user->id,
            'applied_at' => now()->subDays(6),
            'moved_at' => now()->subDay(),
        ]);

        $service = app(CandidateApplicationStageService::class);

        $converted = $service->moveToStage($application, 'hired', [
            'actor_id' => $user->id,
            'action' => 'decision',
            'occurred_at' => '2026-05-11 09:00:00',
        ]);
        $second = $service->moveToStage($converted->fresh(), 'hired', [
            'actor_id' => $user->id,
            'action' => 'decision',
            'occurred_at' => '2026-05-11 09:05:00',
        ]);

        $personnel = Personnel::query()->findOrFail($converted->personnel_id);

        $this->assertSame('Əliyev', $personnel->surname);
        $this->assertSame('Murad', $personnel->name);
        $this->assertSame('Test', $personnel->patronymic);
        $this->assertSame($candidate->phone, $personnel->mobile);
        $this->assertSame((string) $candidate->birthdate, (string) $personnel->birthdate);
        $this->assertSame($opening->structure_id, $personnel->structure_id);
        $this->assertSame($opening->position_id, $personnel->position_id);
        $this->assertSame($user->id, $personnel->added_by);
        $this->assertSame($converted->personnel_id, $second->personnel_id);
        $this->assertSame(1, Personnel::query()->count());
        $this->assertDatabaseHas('candidate_stage_events', [
            'candidate_application_id' => $application->id,
            'action' => 'converted_to_personnel',
            'actor_id' => $user->id,
        ]);
    }

    private function makeCandidateAndOpening(string $pack, bool $withApprovedRequisition = false): array
    {
        $this->seedPersonnelConversionReferences();

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
            'phone' => '0501234567',
            'birthdate' => now()->subYears(28)->toDateString(),
            'gender' => 1,
            'creator_id' => $user->id,
        ]);

        $requisition = null;
        if ($withApprovedRequisition) {
            $requisition = JobRequisition::query()->create([
                'title' => 'Approved opening '.$pack,
                'structure_id' => $structure->id,
                'position_id' => $position->id,
                'profile_pack' => $pack,
                'status' => 'approved',
                'approval_status' => 'approved',
                'requested_by' => $user->id,
                'owner_id' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        }

        $opening = JobOpening::query()->create([
            'job_requisition_id' => $requisition?->id,
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

    private function seedPersonnelConversionReferences(): void
    {
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        DB::table('countries')->insertOrIgnore([
            'id' => 11,
            'code' => 'AZ',
        ]);

        DB::table('education_degrees')->insertOrIgnore([
            'id' => 100,
            'title_az' => 'Ali',
            'title_en' => 'Higher',
        ]);

        DB::table('work_norms')->insertOrIgnore([
            'id' => 10,
            'name_az' => 'Tam ştat',
            'name_en' => 'Full-time',
        ]);
    }
}
