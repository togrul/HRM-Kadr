<?php

namespace Tests\Feature\Console;

use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CandidateAtsQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_ats_query_budget_passes_with_application_dataset(): void
    {
        $this->makeApplication();

        $exitCode = Artisan::call('candidates:ats-query-budget', [
            '--json' => true,
            '--aging-budget' => 15,
            '--application-budget' => 18,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertCount(2, data_get($payload, 'results'));
    }

    private function makeApplication(): CandidateApplication
    {
        $actor = User::factory()->create();
        $structure = Structure::query()->create(['name' => 'ATS Budget HQ', 'shortname' => 'ATS']);
        $position = Position::query()->create(['name' => 'ATS Specialist']);
        $status = AppealStatus::query()->create(['name' => 'Yeni', 'locale' => app()->getLocale()]);
        $candidate = Candidate::query()->create([
            'surname' => 'Namizəd',
            'name' => 'Budget',
            'patronymic' => 'ATS',
            'structure_id' => $structure->id,
            'status_id' => $status->id,
            'height' => 180,
            'creator_id' => $actor->id,
        ]);
        $requisition = JobRequisition::query()->create([
            'title' => 'ATS Specialist',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => 'private',
            'requested_by' => $actor->id,
            'owner_id' => $actor->id,
            'approval_status' => 'pending',
        ]);
        $requisition->forceFill(['created_at' => now()->subDays(20)])->save();
        $opening = JobOpening::query()->create([
            'job_requisition_id' => $requisition->id,
            'title' => 'ATS Specialist',
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'profile_pack' => 'private',
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
        ]);

        return CandidateApplication::query()->create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'assigned_recruiter_id' => $actor->id,
            'current_stage' => 'interview',
            'status' => 'active',
        ]);
    }
}
