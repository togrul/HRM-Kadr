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

    $component = Livewire::withQueryParams(['year' => 2026, 'month' => 9])->test(Dashboard::class);
    $html = $component->html();
    $stats = loadAttendanceIsland($component, 'attendance-overview-stats');

    expect($html)
        ->toContain(__('attendance::dashboard.cards.needs_attention'))
        ->toContain(e(route('attendance', ['tab' => 'manual', 'year' => 2026, 'month' => 9])))
        ->toContain(e(route('attendance', ['tab' => 'exceptions', 'year' => 2026, 'month' => 9])))
        ->and($stats)
        ->toContain('>176<')
        ->toContain(__('attendance::dashboard.units.hours'))
        ->toContain(__('attendance::dashboard.cards.attendance_statistics'));
});

it('paints the overview statistics as a skeleton first and reads the month aggregates only on the deferred request', function (): void {
    $this->actingAs(attendanceAdmin());

    \Illuminate\Support\Facades\DB::enableQueryLog();
    $component = Livewire::withQueryParams(['year' => 2026, 'month' => 9])->test(Dashboard::class);
    $firstPaint = collect(\Illuminate\Support\Facades\DB::getQueryLog())->pluck('query')->implode("\n");

    expect($component->html())
        ->toContain('aria-busy="true"')
        ->toContain('wire:init="__lazyLoadIsland"')
        ->not->toContain(__('attendance::dashboard.cards.attendance_statistics'))
        ->and($firstPaint)->not->toContain('attendance_daily_structure_summaries')
        ->and($firstPaint)->not->toContain('attendance_daily_ledgers');

    expect(loadAttendanceIsland($component, 'attendance-overview-stats'))
        ->toContain(__('attendance::dashboard.cards.attendance_statistics'))
        ->toContain('>176<');
});

it('re-renders the statistics for the new month when the month stepper moves', function (): void {
    $this->actingAs(attendanceAdmin());

    $component = Livewire::withQueryParams(['year' => 2026, 'month' => 9])->test(Dashboard::class);
    loadAttendanceIsland($component, 'attendance-overview-stats');

    $component->call('shiftMonth', 1)->call('shiftMonth', 1);

    // November 2026: 21 workdays × 8 h.
    expect($component->html())->toContain('>168<')
        ->and($component->get('month'))->toBe(11);
});

/**
 * Replays the request the browser sends for a deferred island.
 */
function loadAttendanceIsland(\Livewire\Features\SupportTesting\Testable $component, string $island): string
{
    $component->update(calls: [[
        'method' => '__lazyLoadIsland',
        'params' => [],
        'path' => '',
        'metadata' => ['island' => ['name' => $island, 'mode' => 'morph']],
    ]]);

    return implode('', $component->effects['islandFragments'] ?? []);
}

it('groups the sections into work, review and settings in the header, leaving the panel to the tree', function (): void {
    $this->actingAs(attendanceAdmin());

    $html = Livewire::test(Dashboard::class)->html();
    $panel = fn (string $group): string => preg_match('#<ul[^>]*data-group="'.$group.'"[^>]*>(.*?)</ul>#s', $html, $m) ? $m[1] : '';

    // Every chip <li> must sit inside a <ul>, or the browser draws a bullet beside it.
    expect(substr_count($html, '<li '))->toBeGreaterThan(0)
        ->and(preg_match('#</ul>\s*(?:(?!<ul).)*<li #s', $html))->toBe(0)
        ->and($html)->not->toContain('attendance-section-nav')
        ->and($html)->toContain(__('attendance::dashboard.tabs.work_group'))
        ->and($panel('work_group'))->toContain(__('attendance::dashboard.tabs.puantaj'))
        ->and($panel('review_group'))->toContain(__('attendance::dashboard.tabs.exceptions'))
        ->and($panel('settings_group'))->toContain(__('attendance::dashboard.tabs.shifts'))
        // only the active section's group is shown on load
        ->and($html)->toMatch('#<ul[^>]*data-group="review_group"[^>]*style="display: none"#s');
});

it('makes every attention card an obvious link, quieter when its queue is empty', function (): void {
    $this->actingAs(attendanceAdmin());

    $html = Livewire::withQueryParams(['year' => 2026, 'month' => 9])->test(Dashboard::class)->html();

    expect($html)
        ->toContain(__('attendance::dashboard.cards.open_queue'))
        ->toContain(__('attendance::dashboard.cards.queue_empty'))
        ->toContain('focus-visible:ring-2')
        ->toContain('group-hover:translate-x-0.5');
});
