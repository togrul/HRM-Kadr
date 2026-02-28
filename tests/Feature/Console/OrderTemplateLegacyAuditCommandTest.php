<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OrderTemplateLegacyAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_json_summary(): void
    {
        $exitCode = Artisan::call('orders:templates:legacy-audit', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('recommended_actions', $payload);
    }
}
