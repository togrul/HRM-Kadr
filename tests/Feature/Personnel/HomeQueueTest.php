<?php

namespace Tests\Feature\Personnel;

use App\Models\AttendanceManualEntry;
use App\Models\OrderLog;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Modules\Attendance\Contracts\ManualEntryApprover;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Personnel\Livewire\Home;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The home page's attention tiles open their oldest rows in place, and the
 * approvable queues decide them there without a trip to the module.
 */
class HomeQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tile_opens_its_oldest_rows_and_unknown_tiles_are_ignored(): void
    {
        $this->actingAsViewer(['show-attendance-manual']);
        $this->seedPersonnel();
        $this->manualEntry(today()->toDateString());
        $this->manualEntry(today()->subDays(3)->toDateString());

        $home = Livewire::test(Home::class)
            ->call('toggleQueue', 'attendance_pending')
            ->assertSet('queue', 'attendance_pending')
            ->call('toggleQueue', 'expiring_documents')
            ->assertSet('queue', 'attendance_pending');

        $items = $home->instance()->queueItems;

        $this->assertCount(2, $items);
        $this->assertSame('Test Personnel Home', $items[0]['title']);
        $this->assertStringStartsWith(today()->subDays(3)->format('d.m.Y'), $items[0]['meta']);

        $home->call('toggleQueue', 'attendance_pending')->assertSet('queue', null);
    }

    public function test_an_approver_decides_a_manual_entry_in_place(): void
    {
        $user = $this->actingAsViewer(['show-attendance-manual']);
        $this->seedPersonnel();
        $entry = $this->manualEntry(today()->toDateString());

        $this->mock(ManualEntryApprover::class, function ($mock) use ($entry, $user): void {
            $mock->shouldReceive('canApprove')->andReturn(true);
            $mock->shouldReceive('approve')->once()->with($entry->id, Mockery::on(fn (User $u): bool => $u->is($user)));
        });

        Livewire::test(Home::class)
            ->call('toggleQueue', 'attendance_pending')
            ->assertSet('queueDecidable', true)
            ->assertSee(__('personnel::home.queue.approve'))
            ->call('decide', $entry->id, true)
            ->assertDispatched('notify');
    }

    public function test_a_viewer_without_the_approve_right_sees_no_buttons_and_cannot_decide(): void
    {
        $this->actingAsViewer(['show-attendance-manual']);
        $this->seedPersonnel();
        $entry = $this->manualEntry(today()->toDateString());

        Livewire::test(Home::class)
            ->call('toggleQueue', 'attendance_pending')
            ->assertSet('queueDecidable', false)
            ->assertDontSee(__('personnel::home.queue.approve'))
            ->call('decide', $entry->id, true)
            ->assertForbidden();

        $this->assertSame('pending', $entry->fresh()->approval_status);
    }

    public function test_unsigned_orders_open_read_only_with_a_link_to_the_order(): void
    {
        $this->actingAsViewer(['show-orders']);

        OrderLog::query()->create([
            'order_id' => 1,
            'order_no' => 'A-7',
            'given_date' => '2026-08-01',
            'given_by' => 'Komandir',
            'given_by_rank' => 'Polkovnik',
            'status_id' => OrderIssueService::STATUS_PENDING,
        ]);

        $home = Livewire::test(Home::class)
            ->call('toggleQueue', 'unsigned_orders')
            ->assertSet('queueDecidable', false)
            ->assertDontSee(__('personnel::home.queue.approve'));

        $item = $home->instance()->queueItems[0];

        $this->assertSame('№ A-7', $item['title']);
        $this->assertSame(route('orders', ['search' => ['order_no' => 'A-7']]), $item['url']);
    }

    public function test_vacation_rows_list_only_requests_the_reviewer_may_decide(): void
    {
        // No review permission and no approver link: nothing to decide.
        $this->actingAsViewer(['show-vacations']);
        $this->seedPersonnel();
        $mine = $this->vacation(approverPersonnelId: null);

        $this->assertSame([], Livewire::test(Home::class)->call('toggleQueue', 'vacation_requests')->instance()->queueItems);

        $this->actingAsViewer(['show-vacations', 'review-self-service-requests']);
        $items = Livewire::test(Home::class)->call('toggleQueue', 'vacation_requests')->instance()->queueItems;

        $this->assertSame([$mine->id], array_column($items, 'id'));
    }

    public function test_a_reviewer_rejects_a_vacation_request_in_place(): void
    {
        $this->actingAsViewer(['show-vacations', 'review-self-service-requests']);
        $this->seedPersonnel();
        $vacation = $this->vacation(approverPersonnelId: null);

        Livewire::test(Home::class)
            ->call('toggleQueue', 'vacation_requests')
            ->call('decide', $vacation->id, false)
            ->assertDispatched('notify');

        $this->assertSame('rejected', $vacation->fresh()->approval_status);
    }

    private function seedPersonnel(): void
    {
        DB::table('personnels')->insert([
            'tabel_no' => 'T-1001',
            'surname' => 'Test',
            'name' => 'Personnel',
            'patronymic' => 'Home',
            'birthdate' => '1990-01-01',
            'mobile' => '0500000001',
            'nationality_id' => 1,
            'pin' => 'PIN1001',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => 1,
            'work_norm_id' => 1,
        ]);
    }

    private function manualEntry(string $date): AttendanceManualEntry
    {
        return AttendanceManualEntry::query()->create([
            'tabel_no' => 'T-1001',
            'date' => $date,
            'worked_minutes' => 480,
            'entered_by' => 1,
            'approval_status' => 'pending',
        ]);
    }

    private function vacation(?int $approverPersonnelId): PersonnelVacation
    {
        return PersonnelVacation::query()->create([
            'tabel_no' => 'T-1001',
            'vacation_places' => 'Qax',
            'duration' => 4,
            'start_date' => today()->addWeek()->toDateString(),
            'end_date' => today()->addWeek()->addDays(3)->toDateString(),
            'return_work_date' => today()->addWeek()->addDays(4)->toDateString(),
            'order_given_by' => 'Employee Self-Service',
            'vacation_days_total' => 0,
            'remaining_days' => 0,
            'approval_status' => 'pending',
            'submission_source' => 'employee_self_service',
            'approver_personnel_id' => $approverPersonnelId,
            'added_by' => 1,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function actingAsViewer(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        return $user;
    }
}
