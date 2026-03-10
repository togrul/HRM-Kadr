<?php

namespace Tests\Feature\Leaves;

use App\Models\LeaveType;
use App\Models\User;
use App\Modules\Leaves\Livewire\AddLeave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeaveFormMaxDaysNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_leave_type_shows_max_days_hint(): void
    {
        $this->actingAs($this->userWithCreatePermission());

        $leaveType = LeaveType::query()->create([
            'name' => 'Xəstəlik icazəsi',
            'max_days' => 14,
            'requires_document' => true,
        ]);

        Livewire::test(AddLeave::class)
            ->set('leave.leave_type_id', $leaveType->id)
            ->assertSee(__('leaves::common.labels.max_days_short', ['days' => 14]));
    }

    public function test_exceeding_max_days_shows_non_blocking_notice(): void
    {
        $this->actingAs($this->userWithCreatePermission());

        $leaveType = LeaveType::query()->create([
            'name' => 'Casual leave',
            'max_days' => 3,
            'requires_document' => false,
        ]);

        Livewire::test(AddLeave::class)
            ->set('leave.leave_type_id', $leaveType->id)
            ->set('leave.starts_at', '2026-03-10')
            ->set('leave.ends_at', '2026-03-14')
            ->assertSet('leave.total_days', 5)
            ->assertSee(__('leaves::common.messages.max_days_notice_title'))
            ->assertSee(__('leaves::common.messages.max_days_notice_body', [
                'type' => 'Casual leave',
                'selected' => 5,
                'max' => 3,
            ]));
    }

    public function test_document_is_required_when_selected_leave_type_requires_it(): void
    {
        $this->actingAs($this->userWithCreatePermission());

        $leaveType = LeaveType::query()->create([
            'name' => 'Sick leave',
            'max_days' => 14,
            'requires_document' => true,
        ]);

        Livewire::test(AddLeave::class)
            ->set('leave.leave_type_id', $leaveType->id)
            ->call('store')
            ->assertHasErrors(['leave.document_path']);
    }

    private function userWithCreatePermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-leaves', 'web'));

        return $user;
    }
}
