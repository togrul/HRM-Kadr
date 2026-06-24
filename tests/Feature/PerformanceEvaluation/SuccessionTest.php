<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceCycle;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\SuccessionPlan;
use App\Models\Structure;
use App\Models\TalentAssessment;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\SuccessionService;
use App\Modules\PerformanceEvaluation\Livewire\SuccessionWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SuccessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_nine_box_places_a_person_by_performance_and_potential(): void
    {
        $cycle = $this->cycle();
        $person = $this->makePersonnel('Əliyev');
        $service = app(SuccessionService::class);

        // high performance + high potential → top-right box 9
        $service->upsertAssessment($person->id, $cycle->id, 3, 3);

        $grid = $service->nineBox($cycle->id);
        $box9 = collect($grid)->firstWhere('index', 9);

        $this->assertNotNull($box9);
        $this->assertCount(1, $box9['people']);
        $this->assertSame($person->id, $box9['people'][0]['personnel_id']);

        // re-assessing the same person+cycle moves them, not duplicates
        $service->upsertAssessment($person->id, $cycle->id, 1, 1);
        $this->assertSame(1, TalentAssessment::count());
        $box1 = collect($service->nineBox($cycle->id))->firstWhere('index', 1);
        $this->assertCount(1, $box1['people']);
    }

    public function test_authorized_user_assesses_a_person_via_the_workspace(): void
    {
        $cycle = $this->cycle();
        $person = $this->makePersonnel('Məmmədov');
        $this->actingAs($this->userWith(['show-performance-evaluation', 'manage-performance-evaluation']));

        Livewire::test(SuccessionWorkspace::class)
            ->assertSet('cycleId', $cycle->id)
            ->call('openAssess')
            ->assertSet('isSideModalOpen', true)
            ->set('assessForm.personnel_id', $person->id)
            ->set('assessForm.performance_level', 3)
            ->set('assessForm.potential_level', 2)
            ->call('saveAssessment')
            ->assertHasNoErrors()
            ->assertSet('isSideModalOpen', false);

        $assessment = TalentAssessment::where('personnel_id', $person->id)->firstOrFail();
        $this->assertSame(6, $assessment->box); // perf 3, pot 2 → box 6
    }

    public function test_succession_plan_gets_a_candidate_with_readiness(): void
    {
        $successor = $this->makePersonnel('Vəliyev');
        $service = app(SuccessionService::class);

        $plan = $service->createPlan(['role_title' => 'Şöbə müdiri', 'risk_of_loss' => 'high', 'impact_of_loss' => 'high']);
        $service->addCandidate($plan->id, $successor->id, 'ready_now');

        $plan = SuccessionPlan::with('candidates')->find($plan->id);
        $this->assertCount(1, $plan->candidates);
        $this->assertSame('ready_now', $plan->candidates->first()->readiness);

        // adding the same person again does not duplicate
        $service->addCandidate($plan->id, $successor->id, '1_2_years');
        $this->assertSame(1, $plan->candidates()->count());
    }

    public function test_talent_pool_gets_members_without_duplicates(): void
    {
        $member = $this->makePersonnel('Hüseynov');
        $service = app(SuccessionService::class);

        $pool = $service->createPool(['name' => 'HiPo 2026', 'pool_type' => 'hipo']);
        $service->addMember($pool->id, $member->id);
        $service->addMember($pool->id, $member->id); // same person again

        $pool = \App\Models\TalentPool::with('members')->find($pool->id);
        $this->assertCount(1, $pool->members);
        $this->assertSame($member->id, (int) $pool->members->first()->personnel_id);
    }

    public function test_viewing_requires_permission(): void
    {
        $this->cycle();
        $this->actingAs(User::factory()->create());

        Livewire::test(SuccessionWorkspace::class)->assertForbidden();
    }

    private function cycle(): PerformanceCycle
    {
        return PerformanceCycle::query()->create([
            'name' => '2026 illik', 'period_start' => '2026-01-01', 'period_end' => '2026-12-31', 'status' => 'active',
        ]);
    }

    private function makePersonnel(string $surname): Personnel
    {
        $structure = Structure::query()->create(['name' => 'Şöbə '.Str::random(4), 'shortname' => 'S'.Str::upper(Str::random(3))]);
        $position = Position::query()->create(['name' => 'Vəzifə '.Str::random(4)]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname, 'name' => 'Ad', 'patronymic' => 'Ata',
            'birthdate' => '1985-01-01', 'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com', 'mobile' => '994500000000', 'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'X', 'education_degree_id' => 1, 'work_norm_id' => 1,
            'structure_id' => $structure->id, 'position_id' => $position->id,
            'join_work_date' => '2015-01-01', 'added_by' => 1, 'is_pending' => false,
        ]));
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }
}
