<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CandidateListRenderBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_render_metrics_for_candidate_list_flows(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-candidates', 'web');
        Permission::findOrCreate('add-candidates', 'web');
        $user->givePermissionTo('show-candidates');
        $user->givePermissionTo('add-candidates');

        $exitCode = Artisan::call('candidates:list-render-benchmark', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame('ok', data_get($results, 'candidate_list_render.status'));
        $this->assertSame('ok', data_get($results, 'candidate_filter_update.status'));
        $this->assertSame('ok', data_get($results, 'candidate_add_modal_open.status'));
        $this->assertGreaterThan(0, (int) data_get($results, 'candidate_list_render.response_bytes'));
    }
}
