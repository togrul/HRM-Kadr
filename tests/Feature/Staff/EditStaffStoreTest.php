<?php

namespace Tests\Feature\Staff;

use App\Models\StaffSchedule;
use App\Models\User;
use App\Modules\Staff\Livewire\EditStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EditStaffStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_rows_are_deleted_once_and_kept_rows_persist(): void
    {
        $this->actingAs($this->authorizedUser());
        $this->seedStructureAndPosition();

        $kept = StaffSchedule::create(['structure_id' => 2, 'position_id' => 1, 'total' => 2, 'filled' => 0, 'vacant' => 2]);
        $removed = StaffSchedule::create(['structure_id' => 2, 'position_id' => 1, 'total' => 1, 'filled' => 0, 'vacant' => 1]);

        $component = Livewire::test(EditStaff::class, ['staffModel' => 2]);

        // Drop the second row from the edited set, keep the first.
        $component
            ->set('staff', [[
                'id' => $kept->id,
                'structure_id' => 2,
                'position_id' => 1,
                'total' => 5,
                'filled' => 0,
                'vacant' => 5,
            ]])
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('staff_schedules', ['id' => $removed->id]);
        $this->assertDatabaseHas('staff_schedules', ['id' => $kept->id, 'total' => 5]);
    }

    public function test_removal_is_correct_regardless_of_kept_row_count(): void
    {
        // Regression: deletion-set computation + destroy() were previously inside the
        // upsert loop, so they re-ran for every kept row. With several kept rows this
        // must still delete exactly the removed rows and update every kept row.
        $this->actingAs($this->authorizedUser());
        $this->seedStructureAndPosition();

        $keptA = StaffSchedule::create(['structure_id' => 2, 'position_id' => 1, 'total' => 1, 'filled' => 0, 'vacant' => 1]);
        $keptB = StaffSchedule::create(['structure_id' => 2, 'position_id' => 1, 'total' => 1, 'filled' => 0, 'vacant' => 1]);
        $removed = StaffSchedule::create(['structure_id' => 2, 'position_id' => 1, 'total' => 1, 'filled' => 0, 'vacant' => 1]);

        Livewire::test(EditStaff::class, ['staffModel' => 2])
            ->set('staff', [
                ['id' => $keptA->id, 'structure_id' => 2, 'position_id' => 1, 'total' => 3, 'filled' => 0, 'vacant' => 3],
                ['id' => $keptB->id, 'structure_id' => 2, 'position_id' => 1, 'total' => 4, 'filled' => 0, 'vacant' => 4],
            ])
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('staff_schedules', ['id' => $removed->id]);
        $this->assertDatabaseHas('staff_schedules', ['id' => $keptA->id, 'total' => 3]);
        $this->assertDatabaseHas('staff_schedules', ['id' => $keptB->id, 'total' => 4]);
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('edit-staff', 'web'));

        return $user;
    }

    private function seedStructureAndPosition(): void
    {
        DB::table('structures')->insert([
            ['id' => 1, 'name' => 'Root', 'shortname' => 'ROOT', 'parent_id' => null],
            ['id' => 2, 'name' => 'Child', 'shortname' => 'CHILD', 'parent_id' => 1],
        ]);

        DB::table('positions')->insert(['id' => 1, 'name' => 'Specialist']);
    }
}
