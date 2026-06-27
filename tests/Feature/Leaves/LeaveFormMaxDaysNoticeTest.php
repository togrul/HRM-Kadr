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

    public function test_disallowed_document_type_is_rejected(): void
    {
        $this->actingAs($this->userWithCreatePermission());

        $leaveType = LeaveType::query()->create([
            'name' => 'Sick leave',
            'max_days' => 14,
            'requires_document' => true,
        ]);

        Livewire::test(AddLeave::class)
            ->set('leave.leave_type_id', $leaveType->id)
            ->set('leave.document_path', \Illuminate\Http\UploadedFile::fake()->create('payload.php', 4, 'application/x-php'))
            ->call('store')
            ->assertHasErrors(['leave.document_path']);
    }

    public function test_oversized_document_is_rejected(): void
    {
        $this->actingAs($this->userWithCreatePermission());

        $leaveType = LeaveType::query()->create([
            'name' => 'Sick leave',
            'max_days' => 14,
            'requires_document' => true,
        ]);

        // 11 MB > the 10 MB cap.
        Livewire::test(AddLeave::class)
            ->set('leave.leave_type_id', $leaveType->id)
            ->set('leave.document_path', \Illuminate\Http\UploadedFile::fake()->create('scan.pdf', 11 * 1024, 'application/pdf'))
            ->call('store')
            ->assertHasErrors(['leave.document_path']);
    }

    public function test_allowed_document_within_size_passes_validation(): void
    {
        $this->actingAs($this->userWithCreatePermission());

        $leaveType = LeaveType::query()->create([
            'name' => 'Sick leave',
            'max_days' => 14,
            'requires_document' => true,
        ]);

        Livewire::test(AddLeave::class)
            ->set('leave.leave_type_id', $leaveType->id)
            ->set('leave.document_path', \Illuminate\Http\UploadedFile::fake()->create('scan.pdf', 512, 'application/pdf'))
            ->call('store')
            ->assertHasNoErrors(['leave.document_path']);
    }

    public function test_half_day_duration_recalculates_to_single_day_and_summary(): void
    {
        $this->actingAs($this->userWithCreatePermission());

        Livewire::test(AddLeave::class)
            ->set('leave.duration_unit', 'half_day')
            ->set('leave.starts_at', '2026-03-10')
            ->set('leave.partial_day_part', 'first_half')
            ->assertSet('leave.ends_at', '2026-03-10')
            ->assertSet('leave.total_days', 1)
            ->assertSee(__('leaves::common.labels.duration_summary_half_day'));
    }

    public function test_hour_duration_recalculates_minutes_and_summary(): void
    {
        $this->actingAs($this->userWithCreatePermission());

        Livewire::test(AddLeave::class)
            ->set('leave.duration_unit', 'hour')
            ->set('leave.starts_at', '2026-03-10')
            ->set('leave.starts_time', '09:00')
            ->set('leave.ends_time', '11:30')
            ->assertSet('leave.ends_at', '2026-03-10')
            ->assertSet('leave.total_days', 1)
            ->assertSet('leave.total_minutes', 150)
            ->assertSee(__('leaves::common.labels.duration_summary_hour', ['hours' => number_format(2.5, 1)]));
    }

    private function userWithCreatePermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-leaves', 'web'));

        return $user;
    }
}
