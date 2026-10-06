<?php

use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceMonthlySummary;
use App\Models\User;
use App\Modules\Attendance\Application\Services\AttendanceMonthLockService;
use App\Modules\Attendance\Livewire\MonthClose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function monthCloseManager(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('manage-attendance-month-close', 'web'));

    return $user;
}

function monthCloseSummary(bool $locked): AttendanceMonthlySummary
{
    return AttendanceMonthlySummary::query()->create([
        'tabel_no' => 'TB-1',
        'year' => 2026,
        'month' => 7,
        'total_scheduled_minutes' => 0,
        'total_worked_minutes' => 0,
        'total_overtime_minutes' => 0,
        'total_absence_minutes' => 0,
        'total_workdays' => 0,
        'total_present_days' => 0,
        'total_absence_days' => 0,
        'is_locked' => $locked,
    ]);
}

it('refuses to close a month that is already closed', function (): void {
    monthCloseSummary(locked: true);

    app(AttendanceMonthLockService::class)->closeMonth(2026, 7);
})->throws(DomainException::class, 'already closed');

it('refuses to reopen a month that is already open', function (): void {
    monthCloseSummary(locked: false);

    app(AttendanceMonthLockService::class)->unlockMonth(2026, 7);
})->throws(DomainException::class, 'nothing to reopen');

it('keeps a closed month locked when a plain snapshot is taken', function (): void {
    $summary = monthCloseSummary(locked: true);
    Schema::withoutForeignKeyConstraints(fn () => AttendanceDailyLedger::query()->create([
        'tabel_no' => 'TB-1',
        'date' => '2026-07-02',
        'scheduled_minutes' => 480,
        'worked_minutes' => 480,
        'attendance_status' => 'present',
        'is_locked' => true,
    ]));

    app(AttendanceMonthLockService::class)->snapshotMonth(2026, 7, false);

    expect((bool) $summary->fresh()->is_locked)->toBeTrue()
        ->and((int) $summary->fresh()->total_worked_minutes)->toBe(480);
});

it('offers only the close action on an open month, behind a confirmation', function (): void {
    $this->actingAs(monthCloseManager());
    monthCloseSummary(locked: false);

    $html = Livewire::test(MonthClose::class, ['year' => 2026, 'month' => 7])->html();

    expect($html)
        ->toContain(e(__('attendance::month_close.actions.close_month')))
        ->not->toContain(e(__('attendance::month_close.actions.unlock_month')))
        ->toContain('confirm-action')
        ->toContain('$wire.closePeriod()')
        ->not->toContain('wire:click="closePeriod"');
});

it('offers only the reopen action on a closed month, behind a confirmation', function (): void {
    $this->actingAs(monthCloseManager());
    monthCloseSummary(locked: true);

    $html = Livewire::test(MonthClose::class, ['year' => 2026, 'month' => 7])->html();

    expect($html)
        ->toContain(e(__('attendance::month_close.actions.unlock_month')))
        ->not->toContain('$wire.closePeriod()')
        ->toContain('$wire.unlockPeriod()')
        ->not->toContain('wire:click="unlockPeriod"');
});

it('reports a stale close attempt instead of re-closing the month', function (): void {
    $this->actingAs(monthCloseManager());
    $summary = monthCloseSummary(locked: true);

    Livewire::test(MonthClose::class, ['year' => 2026, 'month' => 7])
        ->call('closePeriod')
        ->assertDispatched('notify', type: 'error', message: __('attendance::month_close.messages.already_closed'));

    expect((bool) $summary->fresh()->is_locked)->toBeTrue();
});

it('reports a stale reopen attempt on an open month', function (): void {
    $this->actingAs(monthCloseManager());
    monthCloseSummary(locked: false);

    Livewire::test(MonthClose::class, ['year' => 2026, 'month' => 7])
        ->call('unlockPeriod')
        ->assertDispatched('notify', type: 'error', message: __('attendance::month_close.messages.already_open'));
});
