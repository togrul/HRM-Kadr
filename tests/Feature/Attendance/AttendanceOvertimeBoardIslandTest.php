<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Modules\Attendance\Livewire\OvertimeBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceOvertimeBoardIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_overtime_board_renders_island_regions_for_queue_and_workbench(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance Overtime Admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('approve-attendance-overtime', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        Livewire::test(OvertimeBoard::class, ['year' => 2026, 'month' => 3])
            ->assertSee(__('attendance::overtime.title'))
            ->assertSee(__('attendance::overtime.create.title'))
            ->assertSeeHtml('FRAGMENT:type=island|name=attendance-overtime-queue')
            ->assertSeeHtml('FRAGMENT:type=island|name=attendance-overtime-workbench');
    }
}
