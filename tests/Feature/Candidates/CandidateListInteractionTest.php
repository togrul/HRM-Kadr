<?php

namespace Tests\Feature\Candidates;

use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Candidates\Livewire\CandidateList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function test_recruitment_panel_counts_cost_one_query_and_are_memoized(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));
        $this->actingAs($user);

        foreach (['Ali', 'Vali', 'Nihat'] as $name) {
            $this->makeCandidate($name);
        }

        // A bare instance: rendering the component would warm the memo before the
        // listener is attached, hiding the very thing under test.
        $component = new CandidateList;

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $counts = $component->recruitmentPanelCounts();
        $component->recruitmentPanelCounts();

        // Five separate COUNT()s per render was most of this screen's query budget.
        $this->assertCount(1, $queries);
        $this->assertSame(3, $counts['candidates']);
        $this->assertSame(0, $counts['applications']);
        $this->assertSame(0, $counts['active_candidates']);
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

    public function test_candidate_list_can_filter_by_document_category(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-candidates', 'web'));

        $matching = $this->makeCandidate('Ali');
        $other = $this->makeCandidate('Veli');

        CandidateDocument::query()->create([
            'candidate_id' => $matching->id,
            'display_name' => 'Ali CV',
            'original_name' => 'ali-cv.pdf',
            'file_path' => 'candidates/test/ali-cv.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
            'category' => 'cv',
            'uploaded_by' => $user->id,
        ]);

        CandidateDocument::query()->create([
            'candidate_id' => $other->id,
            'display_name' => 'Veli Medical',
            'original_name' => 'veli-medical.pdf',
            'file_path' => 'candidates/test/veli-medical.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
            'category' => 'medical',
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(CandidateList::class)
            ->set('filter.document_category', 'medical')
            ->call('searchFilter')
            ->assertSet('search.document_category', 'medical')
            ->assertSee($other->fullname_max)
            ->assertDontSee($matching->fullname_max)
            ->call('toggleDocumentCategory', 'medical')
            ->assertSet('search.document_category', null);
    }

    private function makeCandidate(string $name = 'Ali'): Candidate
    {
        $structure = Structure::query()->create([
            'name' => 'Candidate Structure '.$name,
            'shortname' => 'CS'.strtoupper(substr($name, 0, 1)),
        ]);

        $creator = User::factory()->create();
        $status = AppealStatus::query()->create([
            'name' => 'Yeni '.$name,
            'locale' => app()->getLocale(),
        ]);

        return Candidate::query()->create([
            'surname' => 'Aliyev',
            'name' => $name,
            'patronymic' => 'Test',
            'structure_id' => $structure->id,
            'status_id' => $status->id,
            'height' => 180,
            'creator_id' => $creator->id,
        ]);
    }
}
