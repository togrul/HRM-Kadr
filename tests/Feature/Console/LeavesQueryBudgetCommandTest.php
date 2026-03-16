<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeavesQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_query_budget_metrics_for_leaves_flows(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-leaves', 'web');
        Permission::findOrCreate('add-leaves', 'web');
        $user->givePermissionTo('show-leaves');
        $user->givePermissionTo('add-leaves');

        $exitCode = Artisan::call('leaves:query-budget', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame('ok', data_get($results, 'leaves_render.status'));
        $this->assertSame('ok', data_get($results, 'leaves_status_update.status'));
        $this->assertSame('ok', data_get($results, 'leaves_add_modal_open.status'));
    }
}
