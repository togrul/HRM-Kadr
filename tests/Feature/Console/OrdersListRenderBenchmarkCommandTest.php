<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrdersListRenderBenchmarkCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_render_metrics_for_orders_list_flows(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-orders', 'web');
        Permission::findOrCreate('add-orders', 'web');
        $user->givePermissionTo('show-orders');
        $user->givePermissionTo('add-orders');

        $exitCode = Artisan::call('orders:list-render-benchmark', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame('ok', data_get($results, 'orders_render.status'));
        $this->assertSame('ok', data_get($results, 'orders_filter_update.status'));
        $this->assertSame('ok', data_get($results, 'orders_add_modal_open.status'));
        $this->assertGreaterThan(0, (int) data_get($results, 'orders_render.response_bytes'));
    }
}
