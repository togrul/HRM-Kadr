<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Modules\Attendance\Livewire\ManualEntries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceManualEntriesIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_entries_renders_island_regions_for_workbench_and_queue(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance Manual Admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('add-attendance-manual', 'web'),
            Permission::findOrCreate('approve-attendance-manual', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        Livewire::test(ManualEntries::class, ['embedded' => true])
            ->assertSee(__('attendance::manual_entries.titles.form'))
            ->assertSee(__('attendance::manual_entries.titles.queue'))
            ->assertSeeHtml('FRAGMENT:type=island|name=attendance-manual-workbench')
            ->assertSeeHtml('FRAGMENT:type=island|name=attendance-manual-queue');
    }
}
