<?php

namespace Tests\Unit;

use App\Services\Modules\ModuleState;
use App\Services\Profiles\ProfileState;
use PHPUnit\Framework\TestCase;

class ModuleStateProfileTest extends TestCase
{
    public function test_profile_overrides_are_applied_to_module_state(): void
    {
        $catalog = [
            'orders' => [
                'provider' => 'App\\Modules\\Orders\\Providers\\OrdersServiceProvider',
                'enabled' => true,
                'migrations' => '/tmp/orders',
            ],
            'business-trips' => [
                'provider' => 'App\\Modules\\BusinessTrips\\Providers\\BusinessTripsServiceProvider',
                'enabled' => true,
                'migrations' => '/tmp/business-trips',
            ],
        ];

        $profiles = [
            'private' => [
                'modules' => [
                    'business-trips' => false,
                ],
                'features' => [],
            ],
        ];

        $profileState = new ProfileState($profiles, 'private', $catalog);
        $state = new ModuleState($profileState->modules());

        $this->assertTrue($state->enabled('orders'));
        $this->assertFalse($state->enabled('business-trips'));
        $this->assertSame('/tmp/orders', $state->migrationPath('orders'));
    }
}
