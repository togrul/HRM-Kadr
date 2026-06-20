<?php

namespace Tests\Feature\Console;

use App\Models\SelfServiceApprovalRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HrPolicyPackSeedCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_preview_policy_pack_seed_payload(): void
    {
        $exitCode = Artisan::call('hr:policy-pack:seed', [
            '--pack' => 'military',
            '--dry-run' => true,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame('military', data_get($payload, 'pack'));
        $this->assertCount(3, data_get($payload, 'request_types', []));
        $this->assertDatabaseCount('self_service_approval_routes', 0);
    }

    public function test_it_seeds_self_service_route_defaults_for_selected_pack(): void
    {
        $exitCode = Artisan::call('hr:policy-pack:seed', [
            '--pack' => 'public',
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame('public', data_get($payload, 'pack'));
        $this->assertDatabaseHas('self_service_approval_routes', [
            'request_type' => 'vacation',
            'include_primary_approver' => 1,
            'include_upper_approver' => 0,
            'hr_always_included' => 1,
        ]);
        $this->assertSame(3, SelfServiceApprovalRoute::query()->count());
    }
}
