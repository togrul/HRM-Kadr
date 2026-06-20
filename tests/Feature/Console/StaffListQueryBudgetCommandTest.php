<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StaffListQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_query_budget_metrics_for_staff_list_flows(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-staff', 'web');
        Permission::findOrCreate('add-staff', 'web');
        $user->givePermissionTo('show-staff');
        $user->givePermissionTo('add-staff');

        $exitCode = Artisan::call('staff:list-query-budget', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);
        $results = collect(data_get($payload, 'results', []))->keyBy('flow');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame('ok', data_get($results, 'staffs_render.status'));
        $this->assertSame('ok', data_get($results, 'staffs_vacancies_render.status'));
        $this->assertSame('ok', data_get($results, 'staffs_add_modal_open.status'));
    }
}
