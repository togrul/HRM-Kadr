<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Modules\Attendance\Application\Services\AttendanceAuditLogger;
use App\Modules\Attendance\Application\Services\AttendanceSettingsService;
use App\Modules\Attendance\Livewire\AttendanceHistoryLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceHistoryLogTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_log_renders_localized_audit_rows_for_settings_changes(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance History Auditor',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('show-attendance-history', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        app(AttendanceSettingsService::class)->updateGlobal([
            'timezone' => 'Asia/Baku',
            'late_grace_minutes' => 10,
            'early_leave_grace_minutes' => 5,
            'rounding_policy' => 'none',
            'rounding_step_minutes' => 5,
            'overtime_policy' => 'by_approval',
        ], $user->id);

        $activityId = (int) Activity::query()
            ->where('log_name', 'attendance')
            ->latest('id')
            ->value('id');

        Activity::query()->whereKey($activityId)->update([
            'created_at' => '2026-03-05 10:00:00',
            'updated_at' => '2026-03-05 10:00:00',
        ]);

        $this->actingAs($user);

        Livewire::test(AttendanceHistoryLog::class, ['year' => 2026, 'month' => 3])
            ->assertSee(__('attendance::history.title'))
            ->assertSee('Attendance qaydaları yeniləndi')
            ->call('toggleRow', $activityId)
            ->assertSee('Gecikmə güzəşti')
            ->assertSee('Erkən çıxış güzəşti');
    }

    public function test_history_log_translates_calendar_event_field_labels_and_values(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance History Auditor',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('show-attendance-history', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        app(AttendanceAuditLogger::class)->log(
            event: 'calendar.weekend_seeded',
            description: 'Attendance weekend calendar auto-created.',
            properties: [
                'before' => [],
                'after' => [
                    'date' => '2026-03-14',
                    'day_type' => 'weekend',
                    'name' => 'attendance::calendar_regimes.auto_labels.weekend',
                    'scope_type' => 'global',
                    'is_paid' => true,
                ],
            ],
            causerId: $user->id
        );

        $activityId = (int) Activity::query()
            ->where('log_name', 'attendance')
            ->latest('id')
            ->value('id');

        Activity::query()->whereKey($activityId)->update([
            'created_at' => '2026-03-14 10:00:00',
            'updated_at' => '2026-03-14 10:00:00',
        ]);

        $this->actingAs($user);

        Livewire::test(AttendanceHistoryLog::class, ['year' => 2026, 'month' => 3])
            ->assertSee('Avto həftəsonu təqvimi yaradıldı')
            ->call('toggleRow', $activityId)
            ->assertSee('Tarix')
            ->assertSee('Gün növü')
            ->assertSee('Əhatə növü')
            ->assertSee('Ödənişli gün')
            ->assertSee('Həftəsonu')
            ->assertSee('Ümumi')
            ->assertSee('Avto həftəsonu');
    }

    public function test_history_toggle_row_does_not_rerun_totals_query(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance History Auditor',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('show-attendance-history', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        app(AttendanceAuditLogger::class)->log(
            event: 'settings.updated',
            description: 'Attendance settings updated.',
            properties: [
                'before' => ['timezone' => 'Asia/Baku'],
                'after' => ['timezone' => 'UTC'],
            ],
            causerId: $user->id
        );

        $activityId = (int) Activity::query()
            ->where('log_name', 'attendance')
            ->latest('id')
            ->value('id');

        $this->actingAs($user);

        $component = Livewire::test(AttendanceHistoryLog::class, ['year' => 2026, 'month' => 3]);

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $component->call('toggleRow', $activityId);

        $queries = collect(DB::connection()->getQueryLog())->pluck('query');

        $this->assertTrue($queries->isNotEmpty());
        $this->assertFalse(
            $queries->contains(fn (string $query) => str_contains($query, 'total_changes')),
            'Expanding a history row should not rerun totals aggregation.'
        );
    }
}
