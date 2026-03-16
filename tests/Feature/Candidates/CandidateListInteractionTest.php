<?php

namespace Tests\Feature\Candidates;

use App\Models\User;
use App\Models\Candidate;
use App\Models\Structure;
use App\Modules\Candidates\Livewire\CandidateList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CandidateListInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(CandidateList::SETTINGS_CACHE_KEY);
        Cache::forget('appeal-statuses:'.app()->getLocale());
        config()->set('candidates.mode', 'military');
    }

    public function test_candidate_list_can_open_add_candidate_side_menu(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('show-candidates', 'web'),
            Permission::findOrCreate('add-candidates', 'web'),
        ]);

        $this->actingAs($user);

        Livewire::test(CandidateList::class)
            ->call('openSideMenu', 'add-candidate')
            ->assertSet('showSideMenu', 'add-candidate')
            ->assertSet('isSideModalOpen', true);
    }

    public function test_candidate_list_can_rerender_after_status_and_filter_updates(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

        $this->actingAs($user);

        Livewire::test(CandidateList::class)
            ->call('setStatus', 'all')
            ->set('filter.fullname', 'Ali')
            ->call('searchFilter')
            ->assertSet('search.fullname', 'Ali');
    }

    public function test_restore_action_is_forbidden_without_delete_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

        $candidate = $this->makeCandidate();
        $candidate->delete();

        $this->actingAs($user);

        Livewire::test(CandidateList::class)
            ->call('restoreData', $candidate->id)
            ->assertForbidden();
    }

    public function test_force_delete_action_is_forbidden_without_delete_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

        $candidate = $this->makeCandidate();
        $candidate->delete();

        $this->actingAs($user);

        Livewire::test(CandidateList::class)
            ->call('forceDeleteData', $candidate->id)
            ->assertForbidden();
    }

    private function makeCandidate(): Candidate
    {
        $structure = Structure::query()->create([
            'name' => 'Candidate Structure',
            'shortname' => 'CS',
        ]);

        $creator = User::factory()->create();

        return Candidate::query()->create([
            'surname' => 'Aliyev',
            'name' => 'Ali',
            'patronymic' => 'Test',
            'structure_id' => $structure->id,
            'height' => 180,
            'creator_id' => $creator->id,
        ]);
    }
}
