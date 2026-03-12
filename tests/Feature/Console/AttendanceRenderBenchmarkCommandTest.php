<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendanceRenderBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_render_metrics_for_attendance_workbench_flows(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user);

        $exitCode = Artisan::call('attendance:render-benchmark', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame('ok', data_get($results, 'manual_entries_render.status'));
        $this->assertSame('ok', data_get($results, 'overtime_board_render.status'));
        $this->assertSame('ok', data_get($results, 'shift_management_render.status'));
        $this->assertSame('ok', data_get($results, 'calendar_regimes_render.status'));
        $this->assertSame('ok', data_get($results, 'month_close_render.status'));
        $this->assertGreaterThan(0, (int) data_get($results, 'manual_entries_render.response_bytes'));
    }

    private function grantPermissions(User $user): void
    {
        foreach ([
            'show-attendance',
            'add-attendance-manual',
            'approve-attendance-overtime',
            'manage-attendance-shifts',
            'manage-attendance-calendars',
            'manage-attendance-month-close',
            'export-attendance',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo([
            'show-attendance',
            'add-attendance-manual',
            'approve-attendance-overtime',
            'manage-attendance-shifts',
            'manage-attendance-calendars',
            'manage-attendance-month-close',
            'export-attendance',
        ]);
    }
}
