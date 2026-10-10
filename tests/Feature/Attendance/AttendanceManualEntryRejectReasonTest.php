<?php

use App\Models\AttendanceManualEntry;
use App\Models\User;
use App\Modules\Attendance\Livewire\ManualEntries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function pendingManualEntry(User $enteredBy): AttendanceManualEntry
{
    return Schema::withoutForeignKeyConstraints(fn () => AttendanceManualEntry::query()->create([
        'tabel_no' => 'TB-REJ',
        'date' => now()->toDateString(),
        'worked_minutes' => 480,
        'reason' => 'Forgot badge',
        'approval_status' => 'pending',
        'entered_by' => $enteredBy->id,
    ]));
}

function manualApprover(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('approve-attendance-manual', 'web'));

    return grantAllStructures($user);
}

it('will not reject a manual entry without a reason', function (): void {
    $this->actingAs($user = manualApprover());
    $entry = pendingManualEntry($user);

    Livewire::test(ManualEntries::class, ['embedded' => true])
        ->call('reject', $entry->id)
        ->assertHasErrors(['rejectNotes.'.$entry->id => 'required']);

    expect($entry->fresh()->approval_status)->toBe('pending');
});

it('stores the rejection reason on the entry', function (): void {
    $this->actingAs($user = manualApprover());
    $entry = pendingManualEntry($user);

    Livewire::test(ManualEntries::class, ['embedded' => true])
        ->set('rejectNotes.'.$entry->id, 'No supporting document')
        ->call('reject', $entry->id)
        ->assertHasNoErrors();

    expect($entry->fresh())
        ->approval_status->toBe('rejected')
        ->rejection_reason->toBe('No supporting document');
});

it('shows the rejection reason under the status in the queue', function (): void {
    $this->actingAs($user = manualApprover());
    $entry = pendingManualEntry($user);

    Livewire::test(ManualEntries::class, ['embedded' => true])
        ->set('rejectNotes.'.$entry->id, 'Şahid sənədi yoxdur')
        ->call('reject', $entry->id);

    // The queue lives in an island, so a fresh render shows the rejected list.
    Livewire::test(ManualEntries::class, ['embedded' => true, 'queueStatus' => 'rejected'])
        ->assertSee('Şahid sənədi yoxdur');
});
