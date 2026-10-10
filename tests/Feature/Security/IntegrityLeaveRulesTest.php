<?php

namespace Tests\Feature\Security;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\OrderStatus;
use App\Models\PayrollPeriod;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\Leaves\Application\Services\LeaveRecordService;
use App\Modules\Leaves\Livewire\DeleteLeave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * İcazə qaydaları (M5, M6, L2): əmrin icazəsi yalnız əmrlə dəyişir, təsdiqlənmiş icazənin
 * tarixi dəyişəndə təsdiq yenidən tələb olunur, heç kəs öz icazəsini təsdiqləmir, bağlı
 * əmək haqqı ayına düşən icazə ayrıca icazəsiz dəyişmir.
 */
class IntegrityLeaveRulesTest extends TestCase
{
    use RefreshDatabase;

    private Personnel $employee;

    private LeaveType $type;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
            OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
        }

        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
        Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);

        $this->employee = Personnel::withoutEvents(fn () => Personnel::factory()->create([
            'tabel_no' => 'TB-INT', 'surname' => 'Əliyev', 'name' => 'Test', 'patronymic' => 'Oglu',
            'birthdate' => '1990-01-01', 'gender' => 1, 'email' => 'tb-int@example.test', 'mobile' => '0500000000',
            'nationality_id' => 1, 'pin' => 'PINTBINT', 'residental_address' => 'Baku', 'education_degree_id' => 1,
            'structure_id' => 1, 'position_id' => 1, 'work_norm_id' => 1, 'join_work_date' => '2020-01-05',
            'added_by' => 1, 'is_pending' => false,
        ]));
        $this->type = LeaveType::query()->create(['name' => 'Ailə icazəsi', 'max_days' => 0, 'requires_document' => false]);
    }

    public function test_a_leave_written_by_an_order_is_not_edited_or_deleted_outside_the_order(): void
    {
        $user = $this->user(['edit-leaves', 'delete-leaves', 'approve-leaves']);
        $leave = Leave::query()->create([...$this->payload(OrderStatusEnum::APPROVED->value), 'submission_source' => 'order']);
        $records = app(LeaveRecordService::class);

        $this->assertThrows(fn () => $records->update($leave, $this->payload(OrderStatusEnum::APPROVED->value, '2026-10-20', '2026-10-21'), $user), ValidationException::class);
        $this->assertThrows(fn () => $records->delete($leave, $user), ValidationException::class);

        $this->actingAs($user);
        Livewire::test(DeleteLeave::class)->call('setDeleteLeave', $leave->id)->call('deleteLeave')->assertDispatched('addError');

        $this->assertNotNull(Leave::query()->find($leave->id));
        $this->assertSame('2026-10-12', $leave->fresh()->starts_at->toDateString());
    }

    public function test_new_dates_on_an_approved_leave_go_back_for_approval_without_approve_leaves(): void
    {
        $approver = $this->user(['add-leaves', 'approve-leaves']);
        $leave = app(LeaveRecordService::class)->create($this->payload(OrderStatusEnum::APPROVED->value), $approver);
        $this->assertNotNull($leave->approved_at);

        $clerk = $this->user(['edit-leaves']);
        app(LeaveRecordService::class)->update($leave, $this->payload(OrderStatusEnum::APPROVED->value, '2026-10-20', '2026-10-22'), $clerk);

        $leave->refresh();
        $this->assertSame(OrderStatusEnum::PENDING->value, (int) $leave->status_id);
        $this->assertNull($leave->approved_at);
        $this->assertNull($leave->approved_by);

        // An approver changing the dates re-stamps the approval with their own decision.
        app(LeaveRecordService::class)->update($leave, $this->payload(OrderStatusEnum::APPROVED->value, '2026-10-23', '2026-10-24'), $approver);
        $leave->refresh();
        $this->assertSame(OrderStatusEnum::APPROVED->value, (int) $leave->status_id);
        $this->assertNotNull($leave->approved_at);
    }

    public function test_nobody_records_their_own_leave_as_approved(): void
    {
        $self = $this->user(['add-leaves', 'approve-leaves']);
        UserPersonnelLink::query()->create(['user_id' => $self->id, 'personnel_id' => $this->employee->id, 'resolution_source' => 'manual', 'resolved_at' => now()]);

        try {
            app(LeaveRecordService::class)->create($this->payload(OrderStatusEnum::APPROVED->value), $self);
            $this->fail('Approving one\'s own leave must be refused.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status_id', $exception->errors());
        }

        $this->assertSame(0, Leave::query()->count());
        app(LeaveRecordService::class)->create($this->payload(OrderStatusEnum::PENDING->value), $self);
        $this->assertSame(1, Leave::query()->count());
    }

    public function test_a_leave_in_a_month_closed_for_pay_needs_the_closed_month_permission(): void
    {
        PayrollPeriod::query()->create([
            'code' => '2026-10', 'year' => 2026, 'month' => 10, 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-31',
            'currency' => 'AZN', 'status' => 'closed',
        ]);
        config(['integration.payroll_owner' => 'self']);
        $records = app(LeaveRecordService::class);
        $clerk = $this->user(['add-leaves', 'edit-leaves', 'delete-leaves']);

        $this->assertThrows(fn () => $records->create($this->payload(OrderStatusEnum::PENDING->value), $clerk), ValidationException::class);

        // A leave from November moved back into October is refused as well.
        $november = $records->create($this->payload(OrderStatusEnum::PENDING->value, '2026-11-02', '2026-11-03'), $clerk);
        $this->assertThrows(fn () => $records->update($november, $this->payload(OrderStatusEnum::PENDING->value, '2026-10-29', '2026-11-03'), $clerk), ValidationException::class);

        $hr = $this->user(['add-leaves', 'edit-leaves', 'delete-leaves', 'edit-closed-month-leaves']);
        $october = $records->create($this->payload(OrderStatusEnum::PENDING->value), $hr);
        $this->assertThrows(fn () => $records->delete($october, $clerk), ValidationException::class);
        $records->delete($october, $hr);
        $this->assertNull(Leave::query()->find($october->id));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $status, string $start = '2026-10-12', string $end = '2026-10-13'): array
    {
        return [
            'tabel_no' => $this->employee->tabel_no,
            'leave_type_id' => $this->type->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'duration_unit' => 'day',
            'reason' => 'Ailə vəziyyəti',
            'status_id' => $status,
        ];
    }

    /**
     * @param  list<string>  $permissions
     */
    private function user(array $permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return grantAllStructures($user);
    }
}
