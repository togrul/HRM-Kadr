<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Models\OrderTemplateMapping;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Models\User;
use App\Services\Orders\OrderLegacyRenderPayloadBuilder;
use App\Services\Orders\OrderMetadataRenderPayloadBuilder;
use App\Services\Orders\OrderPrintPayloadFactory;
use App\Services\Orders\OrderTemplateSnapshotService;
use App\Services\Orders\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderPrintPayloadFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_uses_metadata_builder_when_active_version_has_mappings(): void
    {
        $context = $this->createOrderContext('ORD-META-1');

        $templateVersion = new OrderTemplateVersion;
        $templateVersion->id = 501;
        $mapping = new OrderTemplateMapping;
        $mapping->id = 1;
        $templateVersion->setRelation('mappings', collect([$mapping]));

        $registry = Mockery::mock(TemplateRegistry::class);
        $snapshotService = Mockery::mock(OrderTemplateSnapshotService::class);
        $legacyBuilder = Mockery::mock(OrderLegacyRenderPayloadBuilder::class);
        $metadataBuilder = Mockery::mock(OrderMetadataRenderPayloadBuilder::class);

        $snapshotService->shouldReceive('versionFromSnapshot')
            ->once()
            ->with([])
            ->andReturn(null);

        $registry->shouldReceive('shouldBlockLegacyFallback')
            ->once()
            ->with($context['orderType']->id, 'print')
            ->andReturnFalse();
        $registry->shouldReceive('activeVersionForOrderType')
            ->once()
            ->with($context['orderType']->id)
            ->andReturn($templateVersion);
        $registry->shouldReceive('resolveTemplatePathForOrderType')
            ->once()
            ->with($context['orderType']->id, 'templates/legacy.docx')
            ->andReturn('templates/active.docx');

        $legacyBuilder->shouldReceive('build')
            ->once()
            ->with(Mockery::type(OrderLog::class))
            ->andReturn([
                'scalar_values' => ['day' => '25', 'month' => 'fevral', 'year' => '2026'],
                'rows' => [['content_text' => 'Legacy row']],
                'mode' => 'legacy',
                'template_version_id' => null,
            ]);
        $metadataBuilder->shouldReceive('build')
            ->once()
            ->with(Mockery::type(OrderLog::class), $templateVersion)
            ->andReturn([
                'scalar_values' => ['a' => '1'],
                'rows' => [['content_text' => 'Row']],
                'mode' => 'metadata',
                'template_version_id' => 501,
            ]);

        $payload = (new OrderPrintPayloadFactory($registry, $snapshotService, $legacyBuilder, $metadataBuilder))
            ->build($context['orderLog']);

        $this->assertSame('templates/active.docx', $payload['template_path']);
        $this->assertSame('İşə qəbul', $payload['output_base_name']);
        $this->assertSame('metadata', $payload['context']['render_mode']);
        $this->assertSame(501, $payload['context']['template_version_id']);
    }

    public function test_it_falls_back_to_legacy_builder_when_template_version_has_no_mappings(): void
    {
        $context = $this->createOrderContext('ORD-LEGACY-1');

        $templateVersion = new OrderTemplateVersion;
        $templateVersion->id = 777;
        $templateVersion->setRelation('mappings', collect());

        $registry = Mockery::mock(TemplateRegistry::class);
        $snapshotService = Mockery::mock(OrderTemplateSnapshotService::class);
        $legacyBuilder = Mockery::mock(OrderLegacyRenderPayloadBuilder::class);
        $metadataBuilder = Mockery::mock(OrderMetadataRenderPayloadBuilder::class);

        $snapshotService->shouldReceive('versionFromSnapshot')
            ->once()
            ->with([])
            ->andReturn(null);

        $registry->shouldReceive('shouldBlockLegacyFallback')
            ->once()
            ->with($context['orderType']->id, 'print')
            ->andReturnFalse();
        $registry->shouldReceive('activeVersionForOrderType')
            ->once()
            ->with($context['orderType']->id)
            ->andReturn($templateVersion);
        $registry->shouldReceive('resolveTemplatePathForOrderType')
            ->once()
            ->with($context['orderType']->id, 'templates/legacy.docx')
            ->andReturn('templates/legacy.docx');
        $registry->shouldReceive('shouldLogLegacyFallback')
            ->once()
            ->andReturnFalse();

        $metadataBuilder->shouldNotReceive('build');
        $legacyBuilder->shouldReceive('build')
            ->once()
            ->with(Mockery::type(OrderLog::class))
            ->andReturn([
                'scalar_values' => ['x' => '1'],
                'rows' => [['content_text' => 'Legacy row']],
                'mode' => 'legacy',
                'template_version_id' => null,
            ]);

        $payload = (new OrderPrintPayloadFactory($registry, $snapshotService, $legacyBuilder, $metadataBuilder))
            ->build($context['orderLog']);

        $this->assertSame('templates/legacy.docx', $payload['template_path']);
        $this->assertSame('legacy', $payload['context']['render_mode']);
        $this->assertNull($payload['context']['template_version_id']);
    }

    private function createOrderContext(string $orderNo): array
    {
        OrderCategory::query()->create([
            'id' => 9801,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        Order::query()->create([
            'id' => 9802,
            'order_category_id' => 9801,
            'name' => 'İşə qəbul',
            'content' => 'templates/legacy.docx',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        $orderType = OrderType::query()->create([
            'order_id' => 9802,
            'name' => 'Default',
        ]);

        OrderStatus::query()->create([
            'id' => 10,
            'locale' => 'az',
            'name' => 'Gözləmədə',
        ]);

        $user = User::factory()->create();

        $orderLog = OrderLog::query()->create([
            'order_id' => 9802,
            'order_type_id' => $orderType->id,
            'order_no' => $orderNo,
            'given_date' => '2026-02-25 00:00:00',
            'given_by' => 'Ferid Əsgərov',
            'given_by_rank' => 'general-mayor',
            'status_id' => 10,
            'creator_id' => $user->id,
        ]);

        return [
            'orderType' => $orderType,
            'orderLog' => $orderLog,
        ];
    }
}
