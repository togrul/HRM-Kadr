<?php

namespace Tests\Feature\Orders;

use App\Services\Orders\Document\OrderTemplateProvider;
use App\Services\Orders\Document\OrderTemplateRepository;
use App\Services\Orders\Document\TemplateBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTemplatePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_round_trip_through_the_repository(): void
    {
        $repo = app(OrderTemplateRepository::class);

        $blocks = [
            TemplateBlock::heading('Test'),
            TemplateBlock::clauses(['{{ employee.full_name_dative }} icazə verilsin.']),
            TemplateBlock::signature(['müavin'], 'İmza'),
        ];

        $repo->save('custom_type', 'Xüsusi əmr', $blocks);

        $this->assertTrue($repo->exists('custom_type'));
        $this->assertSame(['custom_type' => 'Xüsusi əmr'], $repo->available());

        $loaded = $repo->blocks('custom_type');
        $this->assertCount(3, $loaded);
        $this->assertSame(TemplateBlock::CLAUSES, $loaded[1]->kind);
        $this->assertSame(['{{ employee.full_name_dative }} icazə verilsin.'], $loaded[1]->data['items']);
    }

    public function test_provider_falls_back_to_presets_when_db_is_empty(): void
    {
        $provider = app(OrderTemplateProvider::class);

        // No rows yet — the built-in presets are still available.
        $this->assertArrayHasKey('leave', $provider->available());
        $this->assertNotEmpty($provider->blocks('leave'));
    }

    public function test_saved_template_overrides_a_preset(): void
    {
        app(OrderTemplateRepository::class)->save('leave', 'Dəyişdirilmiş məzuniyyət', [
            TemplateBlock::heading('Override'),
        ]);

        $provider = app(OrderTemplateProvider::class);

        $this->assertSame('Dəyişdirilmiş məzuniyyət', $provider->available()['leave']);
        $blocks = $provider->blocks('leave');
        $this->assertCount(1, $blocks);
        $this->assertSame('Override', $blocks[0]->data['text']);
    }

    public function test_seed_command_populates_all_presets(): void
    {
        $this->artisan('orders:templates:seed-presets')->assertSuccessful();

        $available = app(OrderTemplateRepository::class)->available();
        $this->assertArrayHasKey('leave', $available);
        $this->assertArrayHasKey('termination_cause', $available);
        $this->assertCount(11, $available);
    }
}
