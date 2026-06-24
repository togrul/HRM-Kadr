<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceGoalService;
use App\Modules\PerformanceEvaluation\Livewire\GoalsWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PerformanceGoalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_rollup_is_the_weighted_average_of_child_key_results(): void
    {
        $cycle = $this->cycle();
        $service = app(PerformanceGoalService::class);

        $parent = $service->createGoal([
            'performance_cycle_id' => $cycle->id, 'goal_type' => 'objective', 'title' => 'Böyümə',
        ]);
        $kr1 = $service->createGoal([
            'performance_cycle_id' => $cycle->id, 'parent_goal_id' => $parent->id, 'goal_type' => 'kpi',
            'title' => 'Gəlir', 'weight_percent' => 50, 'target_value' => 100,
        ]);
        $kr2 = $service->createGoal([
            'performance_cycle_id' => $cycle->id, 'parent_goal_id' => $parent->id, 'goal_type' => 'kpi',
            'title' => 'Müştəri', 'weight_percent' => 50, 'target_value' => 100,
        ]);

        // Check-ins move the current value (latest reading wins).
        $service->addCheckin($kr1, 80);
        $service->addCheckin($kr2, 40);

        $tree = $service->tree($cycle->id);

        $this->assertCount(1, $tree); // single top-level objective
        $this->assertSame('Böyümə', $tree[0]['title']);
        // weighted: (80*50 + 40*50) / 100 = 60
        $this->assertSame(60, $tree[0]['rollup_pct']);
        $this->assertSame(80, $tree[0]['children'][0]['progress_pct']);

        $this->assertSame(40.0, (float) $kr2->fresh()->current_value);
    }

    public function test_authorized_user_creates_a_goal_and_records_a_checkin(): void
    {
        $cycle = $this->cycle();
        $this->actingAs($this->userWith(['show-performance-evaluation', 'manage-performance-evaluation']));

        $component = Livewire::test(GoalsWorkspace::class)
            ->assertSet('cycleId', $cycle->id)
            ->call('openGoalForm')
            ->assertSet('showSideMenu', 'goal-form')
            ->assertSet('isSideModalOpen', true)
            ->set('form.title', 'Satışı artır')
            ->set('form.goal_type', 'kpi')
            ->set('form.target_value', 200)
            ->call('saveGoal')
            ->assertHasNoErrors()
            ->assertSet('isSideModalOpen', false); // modal closes on save

        $goal = PerformanceGoal::where('title', 'Satışı artır')->firstOrFail();
        $this->assertSame($cycle->id, (int) $goal->performance_cycle_id);

        $component->call('startCheckin', $goal->id)
            ->set('checkinValue', 150)
            ->set('checkinNote', 'Yarıyolda')
            ->call('saveCheckin')
            ->assertHasNoErrors();

        $goal->refresh();
        $this->assertSame(150.0, (float) $goal->current_value);
        $this->assertSame(75, $goal->progress_pct); // 150/200
        $this->assertSame(1, $goal->checkins()->count());
    }

    public function test_viewing_requires_permission(): void
    {
        $this->cycle();
        $this->actingAs(User::factory()->create());

        Livewire::test(GoalsWorkspace::class)->assertForbidden();
    }

    public function test_first_cycle_can_be_created_from_the_goals_workspace_when_none_exists(): void
    {
        $this->assertSame(0, PerformanceCycle::count());
        $this->actingAs($this->userWith(['show-performance-evaluation', 'manage-performance-evaluation']));

        Livewire::test(GoalsWorkspace::class)
            ->assertSet('cycleId', null)
            ->set('cycleForm.name', '2026 illik')
            ->set('cycleForm.period_start', '2026-01-01')
            ->set('cycleForm.period_end', '2026-12-31')
            ->call('createCycle')
            ->assertHasNoErrors();

        $this->assertSame(1, PerformanceCycle::count());
        $this->assertNotNull(PerformanceCycle::where('name', '2026 illik')->first());
    }

    public function test_status_can_be_changed_and_done_forces_full_progress(): void
    {
        $cycle = $this->cycle();
        $goal = app(PerformanceGoalService::class)->createGoal([
            'performance_cycle_id' => $cycle->id, 'goal_type' => 'kpi', 'title' => 'X', 'target_value' => 100,
        ]);
        $this->assertSame('active', $goal->status);

        $this->actingAs($this->userWith(['show-performance-evaluation', 'manage-performance-evaluation']));

        Livewire::test(GoalsWorkspace::class)
            ->call('setStatus', $goal->id, 'done')
            ->assertHasNoErrors();

        $goal->refresh();
        $this->assertSame('done', $goal->status);
        $this->assertSame(100, $goal->progress_pct); // "done" forces 100% regardless of current value
    }

    private function cycle(): PerformanceCycle
    {
        return PerformanceCycle::query()->create([
            'name' => '2026 illik',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'status' => 'active',
        ]);
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
