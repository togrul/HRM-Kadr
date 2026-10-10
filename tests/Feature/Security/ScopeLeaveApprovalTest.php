<?php

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\UI\Livewire\Confirmation\AddComment;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * «Təsdiqlə / Rədd et» (AddComment::confirmComment) yalnız server tərəfində yoxlanılan
 * qərardır: icazəsiz istifadəçi, struktur görünürlüyündən kənar icazə və öz icazəsi rədd
 * edilir; düzgün təsdiqləyici isə qərarı verir.
 */
beforeEach(function (): void {
    foreach ([[10, 'Təsdiq gözləyən'], [20, 'Təsdiqlənmiş'], [30, 'Ləğv edilmiş']] as [$id, $name]) {
        OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => $name]);
    }

    DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
    DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
    DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
    Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
    Structure::query()->firstOrCreate(['id' => 2], ['name' => 'Maliyyə', 'shortname' => 'MAL', 'code' => 2, 'level' => 1]);
    Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);

    $this->type = LeaveType::query()->create(['name' => 'Ailə icazəsi', 'max_days' => 0, 'requires_document' => false]);
    $this->employee = approvalPerson('AP-EMP', 1);
    $this->outsider = approvalPerson('AP-OUT', 2);
    $this->reviewerPerson = approvalPerson('AP-REV', 1);
});

function approvalPerson(string $tabelNo, int $structureId): Personnel
{
    return Personnel::withoutEvents(fn (): Personnel => Personnel::query()->forceCreate([
        'tabel_no' => $tabelNo,
        'surname' => 'Soyad'.$tabelNo,
        'name' => 'Test',
        'patronymic' => 'Ata',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'mobile' => '0501112233',
        'nationality_id' => 1,
        'pin' => strtoupper(substr(md5($tabelNo), 0, 7)),
        'residental_address' => 'Bakı',
        'education_degree_id' => 1,
        'structure_id' => $structureId,
        'position_id' => 1,
        'work_norm_id' => 1,
        'join_work_date' => '2021-01-01',
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

function approvalLeave(string $tabelNo, int $typeId, ?int $assignedTo = null): Leave
{
    return Leave::withoutEvents(fn (): Leave => Leave::query()->create([
        'tabel_no' => $tabelNo,
        'leave_type_id' => $typeId,
        'starts_at' => '2026-11-02',
        'ends_at' => '2026-11-03',
        'duration_unit' => 'day',
        'reason' => 'Ailə',
        'status_id' => OrderStatusEnum::PENDING->value,
        'assigned_to' => $assignedTo,
    ]));
}

/**
 * @param  list<string>  $permissions
 * @param  list<int>  $structures
 */
function approvalUser(array $permissions, array $structures = [1], ?Personnel $linkedTo = null): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    grantStructures($user, $structures);

    if ($linkedTo !== null) {
        UserPersonnelLink::query()->create([
            'user_id' => $user->id,
            'personnel_id' => $linkedTo->id,
            'resolution_source' => 'manual',
            'resolved_at' => now(),
        ]);
    }

    return $user;
}

const SCOPE_LEAVE_APPROVER_PERMISSIONS = ['show-leaves', 'edit-leaves', 'approve-leaves'];

it('refuses a decision from a user without the approval permissions', function (): void {
    $leave = approvalLeave('AP-EMP', $this->type->id);
    $this->actingAs(approvalUser(['show-leaves']));

    Livewire::test(AddComment::class)
        ->call('confirmComment', 'APPROVED', $leave->id)
        ->assertForbidden();

    expect((int) $leave->fresh()->status_id)->toBe(OrderStatusEnum::PENDING->value);
});

it('refuses a decision on a leave outside the reviewer\'s structures', function (): void {
    $leave = approvalLeave('AP-OUT', $this->type->id);
    $this->actingAs(approvalUser(SCOPE_LEAVE_APPROVER_PERMISSIONS, [1]));

    Livewire::test(AddComment::class)
        ->call('confirmComment', 'CANCELLED', $leave->id)
        ->assertForbidden();

    expect((int) $leave->fresh()->status_id)->toBe(OrderStatusEnum::PENDING->value);
});

it('refuses a reviewer deciding on their own leave', function (): void {
    $leave = approvalLeave('AP-REV', $this->type->id);
    $this->actingAs(approvalUser(SCOPE_LEAVE_APPROVER_PERMISSIONS, [1], $this->reviewerPerson));

    Livewire::test(AddComment::class)
        ->call('confirmComment', 'APPROVED', $leave->id)
        ->assertForbidden();

    expect((int) $leave->fresh()->status_id)->toBe(OrderStatusEnum::PENDING->value);
});

it('refuses a leave routed to another approver', function (): void {
    $leave = approvalLeave('AP-EMP', $this->type->id, assignedTo: $this->outsider->id);
    $this->actingAs(approvalUser(SCOPE_LEAVE_APPROVER_PERMISSIONS, [1], $this->reviewerPerson));

    Livewire::test(AddComment::class)
        ->call('confirmComment', 'APPROVED', $leave->id)
        ->assertForbidden();
});

it('refuses statuses other than approve / reject', function (): void {
    $leave = approvalLeave('AP-EMP', $this->type->id);
    $this->actingAs(approvalUser(SCOPE_LEAVE_APPROVER_PERMISSIONS, [1]));

    Livewire::test(AddComment::class)
        ->call('confirmComment', 'PENDING', $leave->id)
        ->assertForbidden();
});

it('lets a proper reviewer approve an in-scope leave', function (): void {
    $leave = approvalLeave('AP-EMP', $this->type->id);
    $this->actingAs(approvalUser(SCOPE_LEAVE_APPROVER_PERMISSIONS, [1], $this->reviewerPerson));

    Livewire::test(AddComment::class)
        ->set('comment', 'Razıyam')
        ->call('confirmComment', 'APPROVED', $leave->id)
        ->assertDispatched('leaveApproved');

    expect((int) $leave->fresh()->status_id)->toBe(OrderStatusEnum::APPROVED->value);
});

it('lets the approver the route assigned the leave to reject it', function (): void {
    $leave = approvalLeave('AP-EMP', $this->type->id, assignedTo: $this->reviewerPerson->id);
    $this->actingAs(approvalUser(['show-leaves'], [1], $this->reviewerPerson));

    Livewire::test(AddComment::class)
        ->call('confirmComment', 'CANCELLED', $leave->id)
        ->assertDispatched('leaveRejected');

    expect((int) $leave->fresh()->status_id)->toBe(OrderStatusEnum::CANCELLED->value);
});
