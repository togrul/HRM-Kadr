<?php

namespace Tests\Feature\Candidates;

use App\Models\User;
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

        Cache::forget('settings');
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
}
