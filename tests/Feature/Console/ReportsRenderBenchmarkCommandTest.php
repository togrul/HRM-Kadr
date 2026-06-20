<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Support\Livewire\LivewireComponentProfiler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportsRenderBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_render_metrics_for_reports_surfaces(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user);

        $exitCode = Artisan::call('reports:render-benchmark', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame('ok', data_get($results, 'overview_render.status'));
        $this->assertSame('ok', data_get($results, 'standard_reports_render.status'));
        $this->assertSame('ok', data_get($results, 'dynamic_builder_render.status'));
        $this->assertSame('ok', data_get($results, 'comparisons_render.status'));
        $this->assertGreaterThan(0, (int) data_get($results, 'overview_render.response_bytes'));
    }

    public function test_it_measures_each_reports_surface_once_per_probe(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user);

        $profiler = Mockery::mock(LivewireComponentProfiler::class);
        $profiler->shouldReceive('measureRender')
            ->times(4)
            ->andReturn([
                'render_ms' => 10.5,
                'response_bytes' => 2048,
                'memory_bytes' => 0,
                'peak_memory_bytes' => 0,
            ]);

        $this->app->instance(LivewireComponentProfiler::class, $profiler);

        $exitCode = Artisan::call('reports:render-benchmark', [
            '--json' => true,
        ]);

        $this->assertSame(0, $exitCode);
    }

    private function grantPermissions(User $user): void
    {
        Permission::findOrCreate('show-reports', 'web');
        Permission::findOrCreate('export-reports', 'web');

        $user->givePermissionTo(['show-reports', 'export-reports']);
    }
}
