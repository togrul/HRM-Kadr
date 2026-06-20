<?php

namespace Tests\Feature\Orders;

use App\Services\Orders\Document\OrderTemplateProvider;
use App\Services\Orders\Document\OrderWordTemplateRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Persistence of Word-upload order templates and the composer's template source.
 */
class OrderTemplatePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_templates_round_trip_through_the_repository(): void
    {
        $repo = app(OrderWordTemplateRepository::class);

        $variables = [
            ['token' => 'var_1', 'label' => 'Tam ad', 'source' => 'auto', 'auto_key' => 'employee.full_name_dative', 'field' => null],
            ['token' => 'var_2', 'label' => 'Başlama tarixi', 'source' => 'manual', 'auto_key' => null, 'field' => ['key' => 'var_2', 'type' => 'date']],
        ];

        $repo->save('custom_type', 'Xüsusi əmr', 'none', 'order-templates/custom_type.docx', $variables);

        $this->assertTrue($repo->exists('custom_type'));
        $this->assertSame(['custom_type' => 'Xüsusi əmr'], $repo->available());

        $loaded = $repo->find('custom_type');
        $this->assertSame('order-templates/custom_type.docx', $loaded->docx_path);
        $this->assertCount(2, $loaded->variables);
        $this->assertSame('employee.full_name_dative', $loaded->variables[0]['auto_key']);
        // manualFields() exposes only the per-order inputs.
        $this->assertSame([['key' => 'var_2', 'label' => 'Başlama tarixi', 'type' => 'date']], $loaded->manualFields());
    }

    public function test_provider_lists_active_word_templates(): void
    {
        app(OrderWordTemplateRepository::class)->save('leave', 'Məzuniyyət', 'none', 'order-templates/leave.docx', []);

        $provider = app(OrderTemplateProvider::class);

        $this->assertArrayHasKey('leave', $provider->available());
        $this->assertSame('Məzuniyyət', $provider->available()['leave']);
        $this->assertNotNull($provider->find('leave'));
    }
}
