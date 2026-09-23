<?php

use App\Models\User;
use App\Modules\Attendance\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function attendanceAdmin(): User
{
    $permissions = ['show-attendance', 'manage-attendance', 'manage-attendance-settings', 'manage-attendance-shifts', 'manage-attendance-calendars', 'add-attendance-manual', 'approve-attendance-manual', 'approve-attendance-overtime', 'edit-attendance-exceptions'];
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user->givePermissionTo($permissions);

    return $user;
}

it('leads the overview with linked work queues and reads durations as hours', function (): void {
    $this->actingAs(attendanceAdmin());

    $html = Livewire::withQueryParams(['year' => 2026, 'month' => 9])->test(Dashboard::class)->html();

    expect($html)
        ->toContain(__('attendance::dashboard.cards.needs_attention'))
        ->toContain(e(route('attendance', ['tab' => 'manual', 'year' => 2026, 'month' => 9])))
        ->toContain(e(route('attendance', ['tab' => 'exceptions', 'year' => 2026, 'month' => 9])))
        ->toContain('0 '.__('attendance::dashboard.units.hours_short'))
        ->and(strpos($html, __('attendance::dashboard.cards.needs_attention')))
        ->toBeLessThan(strpos($html, __('attendance::dashboard.cards.attendance_statistics')));
});

it('groups configuration tabs apart from the daily work tabs', function (): void {
    $this->actingAs(attendanceAdmin());

    $html = Livewire::test(Dashboard::class)->html();
    $group = strpos($html, __('attendance::dashboard.tabs.settings_group'));

    // Every tab <li> must sit inside a <ul>, or the browser draws a bullet beside it.
    expect(substr_count($html, '<li '))->toBeGreaterThan(0)
        ->and(preg_match('#</ul>\s*(?:(?!<ul).)*<li #s', $html))->toBe(0)
        ->and($group)->not->toBeFalse()
        ->and(strpos($html, __('attendance::dashboard.tabs.shifts')))->toBeGreaterThan($group)
        ->and(strpos($html, __('attendance::dashboard.tabs.daily_monitor')))->toBeLessThan($group);
});
