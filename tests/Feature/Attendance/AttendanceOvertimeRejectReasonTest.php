<?php

use App\Models\AttendanceOvertimeRequest;
use App\Models\User;
use App\Modules\Attendance\Livewire\OvertimeBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function pendingOvertimeRequest(User $requestedBy): AttendanceOvertimeRequest
{
    return Schema::withoutForeignKeyConstraints(fn () => AttendanceOvertimeRequest::query()->create([
        'tabel_no' => 'TB-OT',
        'date' => now()->toDateString(),
        'requested_minutes' => 60,
        'status' => 'pending',
        'source' => 'manual',
        'requested_by' => $requestedBy->id,
    ]));
}

function overtimeApprover(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('approve-attendance-overtime', 'web'));

    return grantAllStructures($user);
}

it('will not reject an overtime request without a reason', function (): void {
    $this->actingAs($user = overtimeApprover());
    $request = pendingOvertimeRequest($user);

    Livewire::test(OvertimeBoard::class, ['year' => (int) now()->year, 'month' => (int) now()->month])
        ->set('rejectReasons.'.$request->id, 'no')
        ->call('reject', $request->id)
        ->assertHasErrors(['rejectReasons.'.$request->id => 'min']);

    expect($request->fresh()->status)->toBe('pending');
});

it('stores the rejection reason and shows it in the list', function (): void {
    $this->actingAs($user = overtimeApprover());
    $request = pendingOvertimeRequest($user);

    Livewire::test(OvertimeBoard::class, ['year' => (int) now()->year, 'month' => (int) now()->month])
        ->set('rejectReasons.'.$request->id, 'Əlavə iş razılaşdırılmayıb')
        ->call('reject', $request->id)
        ->assertHasNoErrors();

    expect($request->fresh())
        ->status->toBe('rejected')
        ->rejection_reason->toBe('Əlavə iş razılaşdırılmayıb');

    // The list lives in an island, so a fresh render shows the rejected requests.
    Livewire::test(OvertimeBoard::class, ['year' => (int) now()->year, 'month' => (int) now()->month, 'status' => 'rejected'])
        ->assertSee('Əlavə iş razılaşdırılmayıb');
});
