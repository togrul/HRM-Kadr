<?php

namespace Tests\Feature\Staff;

use App\Models\User;
use App\Modules\Staff\Livewire\AddStaff;
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
