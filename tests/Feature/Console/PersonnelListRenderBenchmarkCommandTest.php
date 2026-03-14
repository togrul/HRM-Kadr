<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PersonnelListRenderBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_render_metrics_for_personnel_list_flows(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-personnels', 'web');
        $user->givePermissionTo('show-personnels');

        $exitCode = Artisan::call('personnel:list-render-benchmark', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame('ok', data_get($results, 'all_personnel_render.status'));
        $this->assertSame('ok', data_get($results, 'all_personnel_initial_page.status'));
        $this->assertSame('ok', data_get($results, 'personnel_table_render.status'));
        $this->assertSame('ok', data_get($results, 'all_personnel_status_update.status'));
        $this->assertSame('ok', data_get($results, 'personnel_table_status_render.status'));
        $this->assertSame('ok', data_get($results, 'all_personnel_filter_open.status'));
        $this->assertGreaterThan(0, (int) data_get($results, 'all_personnel_render.response_bytes'));
        $this->assertGreaterThan(0, (int) data_get($results, 'personnel_table_status_render.response_bytes'));
    }
}
