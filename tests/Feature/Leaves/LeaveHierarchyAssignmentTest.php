<?php

namespace Tests\Feature\Leaves;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use App\Modules\Leaves\Livewire\AddLeave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeaveHierarchyAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_form_defaults_to_hierarchy_based_assignment(): void
    {
        $this->actingAs($this->userWithCreatePermission());
        $this->seedLeaveSupportTables();

        $employee = $this->insertPersonnel(101, 'EMP-001', 'Calalli', 'Togrul', 1000, 18);
        $manager = $this->insertPersonnel(102, 'MNG-001', 'Ələkbərova', 'Ayşən', 110, 18);
        $upper = $this->insertPersonnel(103, 'UPR-001', 'Məhərrəmli', 'Rəşid', 100, 18);

        $component = Livewire::test(AddLeave::class)
            ->call('selectPersonnel', $employee->tabel_no, $employee->fullname, 'tabel_no');

        $component
            ->assertSet('leave.assignment_mode', 'auto')
            ->assertSet('leave.assigned_to.id', $manager->id)
            ->assertSet('leave.fallback_approver_personnel_id', null)
            ->assertSet('leave.approval_route_source', 'hierarchy_policy')
            ->assertSee($manager->fullname)
            ->assertSee($upper->fullname)
            ->assertSee(__('leaves::common.labels.automatic_hierarchy'));
    }

    public function test_manual_mode_allows_overriding_hierarchy_assignee_and_persists_manual_route(): void
    {
        $this->actingAs($this->userWithCreatePermission());
        $this->seedLeaveSupportTables();

        $employee = $this->insertPersonnel(101, 'EMP-001', 'Calalli', 'Togrul', 1000, 18);
        $manager = $this->insertPersonnel(102, 'MNG-001', 'Ələkbərova', 'Ayşən', 110, 18);
        $manual = $this->insertPersonnel(104, 'MAN-001', 'Manual', 'Approver', 200, 18);

        $leaveType = LeaveType::query()->create([
            'name' => 'Saatlıq icazə',
            'max_days' => 0,
            'requires_document' => false,
        ]);

        $component = Livewire::test(AddLeave::class)
            ->call('selectPersonnel', $employee->tabel_no, $employee->fullname, 'tabel_no')
            ->call('setAssignmentMode', 'manual')
            ->call('selectPersonnel', $manual->tabel_no, $manual->fullname, 'assigned_to', $manual->id)
            ->set('leave.leave_type_id', $leaveType->id)
            ->set('leave.status_id', OrderStatusEnum::PENDING->value)
            ->set('leave.starts_at', '2026-04-01')
            ->set('leave.ends_at', '2026-04-01')
            ->set('leave.duration_unit', 'day')
            ->set('leave.reason', 'Manual route test')
            ->call('store')
            ->assertDispatched('leaveAdded');

        $leave = Leave::query()->latest('id')->first();

        $this->assertNotNull($leave);
        $this->assertSame($employee->tabel_no, $leave->tabel_no);
        $this->assertSame($manual->id, (int) $leave->assigned_to);
        $this->assertSame('manual_assignment', $leave->approval_route_source);
        $this->assertNotSame($manager->id, (int) $leave->assigned_to);
    }

    public function test_manual_mode_stays_manual_even_when_user_keeps_same_person_as_auto_approver(): void
    {
        $this->actingAs($this->userWithCreatePermission());
        $this->seedLeaveSupportTables();

        $employee = $this->insertPersonnel(101, 'EMP-001', 'Calalli', 'Togrul', 1000, 18);
        $manager = $this->insertPersonnel(102, 'MNG-001', 'Ələkbərova', 'Ayşən', 110, 18);

        $leaveType = LeaveType::query()->create([
            'name' => 'Saatlıq icazə',
            'max_days' => 0,
            'requires_document' => false,
        ]);

        Livewire::test(AddLeave::class)
            ->call('selectPersonnel', $employee->tabel_no, $employee->fullname, 'tabel_no')
            ->call('setAssignmentMode', 'manual')
            ->call('selectPersonnel', $manager->tabel_no, $manager->fullname, 'assigned_to', $manager->id)
            ->set('leave.leave_type_id', $leaveType->id)
            ->set('leave.status_id', OrderStatusEnum::PENDING->value)
            ->set('leave.starts_at', '2026-04-01')
            ->set('leave.ends_at', '2026-04-01')
            ->set('leave.duration_unit', 'day')
            ->set('leave.reason', 'Manual route same approver')
            ->call('store')
            ->assertDispatched('leaveAdded');

        $leave = Leave::query()->latest('id')->first();

        $this->assertNotNull($leave);
        $this->assertSame($manager->id, (int) $leave->assigned_to);
        $this->assertSame('manual_assignment', $leave->approval_route_source);
    }

    public function test_switching_from_auto_to_manual_keeps_current_auto_approver_selected(): void
    {
        $this->actingAs($this->userWithCreatePermission());
        $this->seedLeaveSupportTables();

        $employee = $this->insertPersonnel(101, 'EMP-001', 'Calalli', 'Togrul', 1000, 18);
        $manager = $this->insertPersonnel(102, 'MNG-001', 'Ələkbərova', 'Ayşən', 110, 18);

        Livewire::test(AddLeave::class)
            ->call('selectPersonnel', $employee->tabel_no, $employee->fullname, 'tabel_no')
            ->assertSet('leave.assignment_mode', 'auto')
            ->assertSet('leave.assigned_to.id', $manager->id)
            ->call('setAssignmentMode', 'manual')
            ->assertSet('leave.assignment_mode', 'manual')
            ->assertSet('leave.assigned_to.id', $manager->id)
            ->assertSet('leave.approval_route_source', 'manual_assignment');
    }

    public function test_auto_assignment_persists_full_route_metadata_for_edit_rehydration(): void
    {
        $this->actingAs($this->userWithCreateAndEditPermissions());
        $this->seedLeaveSupportTables();

        $employee = $this->insertPersonnel(101, 'EMP-001', 'Calalli', 'Togrul', 1000, 18);
        $manager = $this->insertPersonnel(102, 'MNG-001', 'Ələkbərova', 'Ayşən', 110, 18);
        $upper = $this->insertPersonnel(103, 'UPR-001', 'Məhərrəmli', 'Rəşid', 100, 18);

        $leaveType = LeaveType::query()->create([
            'name' => 'Saatlıq icazə',
            'max_days' => 0,
            'requires_document' => false,
        ]);

        Livewire::test(AddLeave::class)
            ->call('selectPersonnel', $employee->tabel_no, $employee->fullname, 'tabel_no')
            ->set('leave.leave_type_id', $leaveType->id)
            ->set('leave.status_id', OrderStatusEnum::PENDING->value)
            ->set('leave.starts_at', '2026-04-01')
            ->set('leave.ends_at', '2026-04-01')
            ->set('leave.duration_unit', 'day')
            ->set('leave.reason', 'Auto route persistence')
            ->call('store')
            ->assertDispatched('leaveAdded');

        /** @var Leave $leave */
        $leave = Leave::query()->latest('id')->firstOrFail();

        $this->assertSame($manager->id, (int) $leave->assigned_to);
        $this->assertSame('hierarchy_policy', $leave->approval_route_source);
        $this->assertTrue((bool) $leave->hr_always_included);
        $this->assertNull($leave->fallback_approver_personnel_id);
        $this->assertSame($employee->tabel_no, $leave->tabel_no);
    }

    private function userWithCreatePermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-leaves', 'web'));

        return $user;
    }

    private function userWithCreateAndEditPermissions(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('add-leaves', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('edit-leaves', 'web'));

        return $user;
    }

    private function seedLeaveSupportTables(): void
    {
        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);

        DB::table('education_degrees')->insert([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Бакалавр',
        ]);

        DB::table('structures')->insert([
            ['id' => 1, 'name' => 'Azərbaycan Respublikası Prezidentinin Təhlükəsizlik Xidməti', 'shortname' => 'ARPTX', 'parent_id' => null],
            ['id' => 18, 'name' => 'Texniki vasitələr və rabitə idarəsi', 'shortname' => 'PTX', 'parent_id' => 1],
        ]);

        DB::table('positions')->insert([
            ['id' => 100, 'name' => 'Şöbə müdiri', 'approval_rank' => 2, 'is_approval_target' => 1],
            ['id' => 110, 'name' => 'Bölmə rəisi', 'approval_rank' => 1, 'is_approval_target' => 1],
            ['id' => 200, 'name' => 'Bölük komandiri', 'approval_rank' => 0, 'is_approval_target' => 1],
            ['id' => 1000, 'name' => 'Proqramçı', 'approval_rank' => 0, 'is_approval_target' => 1],
        ]);

        DB::table('work_norms')->insert([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Полный день',
        ]);

        DB::table('order_statuses')->insert([
            'id' => OrderStatusEnum::PENDING->value,
            'name' => 'Gözləyir',
        ]);
    }

    private function insertPersonnel(int $id, string $tabelNo, string $surname, string $name, int $positionId, int $structureId): object
    {
        DB::table('personnels')->insert([
            'id' => $id,
            'tabel_no' => $tabelNo,
            'surname' => $surname,
            'name' => $name,
            'patronymic' => 'Test',
            'has_changed_initials' => false,
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '0501234567',
            'nationality_id' => 1,
            'has_changed_nationality' => false,
            'pin' => 'PIN'.$id,
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => $structureId,
            'position_id' => $positionId,
            'work_norm_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            'is_pending' => false,
        ]);

        return (object) [
            'id' => $id,
            'tabel_no' => $tabelNo,
            'fullname' => sprintf('%s %s Test', $surname, $name),
        ];
    }
}
