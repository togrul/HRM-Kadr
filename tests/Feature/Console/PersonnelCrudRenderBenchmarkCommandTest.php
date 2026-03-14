<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PersonnelCrudRenderBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_render_metrics_for_personnel_crud_flows(): void
    {
        $user = User::factory()->create();

        foreach (['add-personnels', 'edit-personnels'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $exitCode = Artisan::call('personnel:crud-render-benchmark', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame('ok', data_get($results, 'add_personnel_render.status'));
        $this->assertSame('ok', data_get($results, 'edit_personnel_render.status'));
        $this->assertSame('ok', data_get($results, 'edit_personnel_step_8_select.status'));
        $this->assertGreaterThan(0, (int) data_get($results, 'add_personnel_render.response_bytes'));
    }
}
