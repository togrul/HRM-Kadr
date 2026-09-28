<?php

namespace Tests\Feature\Staff;

use App\Models\User;
use App\Modules\Staff\Livewire\AddStaff;
use App\Modules\Staff\Livewire\Staffs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StaffCrudRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_requirement_is_resolved_per_row(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->seedStructuresAndPositions();

        Livewire::test(AddStaff::class)
            ->call('addRow')
            ->call('addRow')
            ->set('structureId', 1)
            ->set('staff.0.structure_id', 1)
            ->set('staff.0.total', 1)
            ->set('staff.1.structure_id', 2)
            ->set('staff.1.total', 1)
            ->call('store')
            ->assertHasErrors(['staff.1.position_id'])
            ->assertHasNoErrors(['staff.0.position_id']);
    }

    public function test_store_does_not_hit_structure_presence_verifier_for_each_row(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->seedStructuresAndPositions();

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test(AddStaff::class)
            ->call('addRow')
            ->call('addRow')
            ->set('structureId', 2)
            ->set('staff.0.structure_id', 2)
            ->set('staff.0.position_id', 1)
            ->set('staff.0.total', 1)
            ->set('staff.1.structure_id', 2)
            ->set('staff.1.position_id', 1)
            ->set('staff.1.total', 1)
            ->call('store')
            ->assertHasNoErrors();

        $queries = collect(DB::getQueryLog())
            ->pluck('query')
            ->map(fn ($query) => strtolower($query));

        $this->assertFalse(
            $queries->contains(
                fn (string $query) => str_contains($query, 'count(*) as aggregate')
                    && str_contains($query, 'structures')
            ),
            'Store should not validate structures with repeated presence-verifier count queries.'
        );
    }

    public function test_deep_tree_branches_render_only_once_opened(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-staff', 'web'));
        $this->actingAs($user);
        $this->seedDeepStructureTree();
        $this->grantStructureScope($user, [1, 2, 3, 4]);

        // The whole org chart used to ship on every render; only the shallow levels do now.
        // Scope is normally resolved from the signed-in user's structures; this fixture
        // grants the whole seeded chart so the tree has something to lazy-render.
        // Asserted on the tree rows' own keys: the contextual panel lists every structure
        // by name, so a name assertion would pass no matter what the tree rendered.
        $row = fn (int $id): string => 'wire:key="staff-node-'.$id.'"';

        $component = Livewire::test(Staffs::class)
            ->assertSee('Root Structure')
            ->assertSee($row(3), escape: false)
            ->assertDontSee($row(4), escape: false);

        $component->call('toggleNode', 3)->assertSee($row(4), escape: false);
        $component->call('toggleNode', 3)->assertDontSee($row(4), escape: false);
        $component->call('expandAllNodes')->assertSee($row(4), escape: false);
        $component->call('collapseAllNodes')->assertDontSee($row(2), escape: false);
    }

    public function test_search_keeps_only_matching_branches_opened_and_highlighted(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-staff', 'web'));
        $this->actingAs($user);
        $this->seedDeepStructureTree();
        $this->grantStructureScope($user, [1, 2, 3, 4, 5]);

        DB::table('structures')->insert(['id' => 5, 'name' => 'Side Branch', 'shortname' => 'SB', 'parent_id' => 1]);
        DB::table('positions')->insert(['id' => 2, 'name' => 'Driver']);
        DB::table('staff_schedules')->insert(['structure_id' => 5, 'position_id' => 2, 'total' => 1, 'filled' => 0, 'vacant' => 1]);

        $row = fn (int $id): string => 'wire:key="staff-node-'.$id.'"';

        // Level Four sits below the eager depth, yet a hit there opens the whole path to it.
        Livewire::test(Staffs::class)
            ->set('search', 'four')
            ->assertSee($row(4), escape: false)
            ->assertDontSee($row(5), escape: false)
            ->assertSee('Level <mark class="rounded bg-amber-100 px-0.5 text-ink">Four</mark>', escape: false)
            ->set('search', 'driv')
            ->assertSee($row(5), escape: false)
            ->assertDontSee($row(2), escape: false)
            ->set('search', 'nothing-like-this')
            ->assertSee(__('staff::common.messages.no_match'));
    }

    public function test_filter_tree_matches_units_and_positions_and_only_vacant(): void
    {
        $node = fn (int $id, string $name, array $positions, array $children, int $vacant): array => [
            'id' => $id, 'name' => $name, 'level' => 1, 'positions' => $positions, 'children' => $children,
            'agg' => ['total' => 0, 'filled' => 0, 'vacant' => $vacant, 'rate' => 0],
        ];
        $position = fn (string $title, int $vacant): array => ['title' => $title, 'vacant' => $vacant];

        $tree = [
            $node(1, 'Maliyyə', [$position('Mühasib', 0)], [
                $node(2, 'Kassa', [$position('Kassir', 2), $position('Mühasib', 0)], [], 2),
            ], 2),
            $node(3, 'Hüquq', [$position('Hüquqşünas', 0)], [], 0),
        ];

        $filter = fn (string $search, bool $onlyVacant): array => (new Staffs)->filterTree($tree, $search, $onlyVacant);
        $ids = function (array $nodes) use (&$ids): array {
            $out = [];
            foreach ($nodes as $n) {
                $out = [...$out, $n['id'], ...$ids($n['children'])];
            }

            return $out;
        };

        $this->assertSame($tree, $filter('', false));

        // unit-name hit keeps the whole subtree
        $this->assertSame([1, 2], $ids($filter('maliyyə', false)));
        $this->assertCount(2, $filter('maliyyə', false)[0]['children'][0]['positions']);

        // position hit keeps only the matching positions and the path to them
        $kassir = $filter('kassir', false);
        $this->assertSame(1, $kassir[0]['id']);
        $this->assertSame([], $kassir[0]['positions']);
        $this->assertSame(['Kassir'], array_column($kassir[0]['children'][0]['positions'], 'title'));

        // only vacant drops closed branches and filled positions
        $vacant = $filter('', true);
        $this->assertSame([1, 2], $ids($vacant));
        $this->assertSame(['Kassir'], array_column($vacant[0]['children'][0]['positions'], 'title'));

        $this->assertSame([], $filter('hüquq', true));
    }

    public function test_only_vacant_chip_hides_fully_staffed_branches(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-staff', 'web'));
        $this->actingAs($user);
        $this->seedDeepStructureTree();
        $this->grantStructureScope($user, [1, 2, 3, 4, 5]);

        DB::table('structures')->insert(['id' => 5, 'name' => 'Side Branch', 'shortname' => 'SB', 'parent_id' => 1]);
        DB::table('staff_schedules')->insert(['structure_id' => 5, 'position_id' => 1, 'total' => 0, 'filled' => 0, 'vacant' => 0]);

        $row = fn (int $id): string => 'wire:key="staff-node-'.$id.'"';

        Livewire::test(Staffs::class)
            ->assertSee($row(5), escape: false)
            ->set('onlyVacant', true)
            ->assertDontSee($row(5), escape: false)
            ->assertSee($row(2), escape: false);
    }

    private function seedDeepStructureTree(): void
    {
        DB::table('structures')->insert([
            ['id' => 1, 'name' => 'Root Structure', 'shortname' => 'ROOT', 'parent_id' => null],
            ['id' => 2, 'name' => 'Level Two', 'shortname' => 'L2', 'parent_id' => 1],
            ['id' => 3, 'name' => 'Level Three', 'shortname' => 'L3', 'parent_id' => 2],
            ['id' => 4, 'name' => 'Level Four', 'shortname' => 'L4', 'parent_id' => 3],
        ]);

        DB::table('positions')->insert(['id' => 1, 'name' => 'Specialist']);

        // Only structures with positions (or a descendant that has them) become nodes.
        DB::table('staff_schedules')->insert([
            ['structure_id' => 4, 'position_id' => 1, 'total' => 2, 'filled' => 1, 'vacant' => 1],
        ]);
    }

    /**
     * Structure scope is resolved through role_structures, so the fixture has to grant it
     * before the tree has anything to render.
     */
    private function grantStructureScope(User $user, array $structureIds): void
    {
        $role = \Spatie\Permission\Models\Role::findOrCreate('staff-tester', 'web');
        $user->assignRole($role);

        DB::table('role_structures')->insert(array_map(
            fn (int $id): array => ['role_id' => $role->id, 'structure_id' => $id],
            $structureIds,
        ));
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-staff', 'web'));

        return $user;
    }

    private function seedStructuresAndPositions(): void
    {
        DB::table('structures')->insert([
            ['id' => 1, 'name' => 'Root Structure', 'shortname' => 'ROOT', 'parent_id' => null],
            ['id' => 2, 'name' => 'Child Structure', 'shortname' => 'CHILD', 'parent_id' => 1],
        ]);

        DB::table('positions')->insert([
            'id' => 1,
            'name' => 'Specialist',
        ]);
    }
}
