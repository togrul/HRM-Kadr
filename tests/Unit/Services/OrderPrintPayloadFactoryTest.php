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
use App\Services\Orders\OrderMetadataRenderPayloadBuilder;
use App\Services\Orders\OrderPrintPayloadFactory;
use App\Services\Orders\OrderTemplateSnapshotService;
use App\Services\Orders\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
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
        $templateVersion->template_path = 'templates/active.docx';
        $mapping = new OrderTemplateMapping;
        $mapping->id = 1;
        $templateVersion->setRelation('mappings', collect([$mapping]));

        $registry = Mockery::mock(TemplateRegistry::class);
        $snapshotService = Mockery::mock(OrderTemplateSnapshotService::class);
        $metadataBuilder = Mockery::mock(OrderMetadataRenderPayloadBuilder::class);

        $snapshotService->shouldReceive('versionFromSnapshot')
            ->once()
            ->with([])
            ->andReturn(null);

        $registry->shouldReceive('activeVersionForOrderType')
            ->once()
            ->with($context['orderType']->id)
            ->andReturn($templateVersion);
        $metadataBuilder->shouldReceive('build')
            ->once()
            ->with(Mockery::type(OrderLog::class), $templateVersion)
            ->andReturn([
                'scalar_values' => ['a' => '1'],
                'rows' => [['content_text' => 'Row']],
                'mode' => 'metadata',
                'template_version_id' => 501,
            ]);

        $payload = (new OrderPrintPayloadFactory($registry, $snapshotService, $metadataBuilder))
            ->build($context['orderLog']);

        $this->assertSame('templates/active.docx', $payload['template_path']);
        $this->assertSame('İşə qəbul', $payload['output_base_name']);
        $this->assertSame('metadata', $payload['context']['render_mode']);
        $this->assertSame(501, $payload['context']['template_version_id']);
        $this->assertSame('registry', $payload['context']['template_source']);
    }

    public function test_it_throws_when_active_template_version_has_no_row_mappings(): void
    {
        $context = $this->createOrderContext('ORD-LEGACY-1');

        $templateVersion = new OrderTemplateVersion;
        $templateVersion->id = 777;
        $scalarOnly = new OrderTemplateMapping;
        $scalarOnly->id = 2;
        $scalarOnly->scope = 'scalar';
        $templateVersion->setRelation('mappings', collect([$scalarOnly]));

        $registry = Mockery::mock(TemplateRegistry::class);
        $snapshotService = Mockery::mock(OrderTemplateSnapshotService::class);
        $metadataBuilder = Mockery::mock(OrderMetadataRenderPayloadBuilder::class);

        $snapshotService->shouldReceive('versionFromSnapshot')
            ->once()
            ->with([])
            ->andReturn(null);

        $registry->shouldReceive('activeVersionForOrderType')
            ->once()
            ->with($context['orderType']->id)
            ->andReturn($templateVersion);
        $metadataBuilder->shouldNotReceive('build');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(__('orders::template_runtime.messages.metadata_mappings_required_for_order_type'));

        (new OrderPrintPayloadFactory($registry, $snapshotService, $metadataBuilder))
            ->build($context['orderLog']);
    }

    public function test_it_throws_when_snapshot_version_has_only_scalar_mappings(): void
    {
        $context = $this->createOrderContext('ORD-SNAPSHOT-SCALAR-1');

        $templateVersion = new OrderTemplateVersion;
        $templateVersion->id = 881;
        $templateVersion->template_path = 'templates/strict.docx';
        $scalarOnly = new OrderTemplateMapping;
        $scalarOnly->id = 3;
        $scalarOnly->scope = 'scalar';
        $templateVersion->setRelation('mappings', collect([$scalarOnly]));

        $snapshot = [
            'render_mode' => 'metadata',
            'template_path' => 'templates/strict.docx',
        ];
        $context['orderLog']->update([
            'template_snapshot' => $snapshot,
            'template_render_mode' => 'metadata',
        ]);
        $context['orderLog']->refresh();

        $registry = Mockery::mock(TemplateRegistry::class);
        $snapshotService = Mockery::mock(OrderTemplateSnapshotService::class);
        $metadataBuilder = Mockery::mock(OrderMetadataRenderPayloadBuilder::class);

        $snapshotService->shouldReceive('versionFromSnapshot')
            ->once()
            ->with($snapshot)
            ->andReturn($templateVersion);
        $metadataBuilder->shouldNotReceive('build');
        $registry->shouldNotReceive('activeVersionForOrderType');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(__('orders::template_runtime.messages.metadata_mappings_required_for_order_type'));

        (new OrderPrintPayloadFactory($registry, $snapshotService, $metadataBuilder))
            ->build($context['orderLog']);
    }

    public function test_it_uses_snapshot_version_and_template_path_when_snapshot_exists(): void
    {
        $context = $this->createOrderContext('ORD-SNAPSHOT-1');

        $templateVersion = new OrderTemplateVersion;
        $templateVersion->id = 880;
        $templateVersion->template_path = 'templates/strict.docx';
        $mapping = new OrderTemplateMapping;
        $mapping->id = 1;
        $templateVersion->setRelation('mappings', collect([$mapping]));

        $snapshot = [
            'render_mode' => 'metadata',
            'template_path' => 'templates/strict.docx',
        ];
        $context['orderLog']->update([
            'template_snapshot' => $snapshot,
            'template_render_mode' => 'metadata',
        ]);
        $context['orderLog']->refresh();

        $registry = Mockery::mock(TemplateRegistry::class);
        $snapshotService = Mockery::mock(OrderTemplateSnapshotService::class);
        $metadataBuilder = Mockery::mock(OrderMetadataRenderPayloadBuilder::class);

        $snapshotService->shouldReceive('versionFromSnapshot')
            ->once()
            ->with($snapshot)
            ->andReturn($templateVersion);
        $registry->shouldNotReceive('activeVersionForOrderType');
        $registry->shouldNotReceive('resolveTemplatePathForOrderType');

        $metadataBuilder->shouldReceive('build')
            ->once()
            ->with(Mockery::type(OrderLog::class), $templateVersion)
            ->andReturn([
                'scalar_values' => ['a' => '1'],
                'rows' => [['content_text' => 'Metadata row']],
                'mode' => 'metadata',
                'template_version_id' => 880,
            ]);

        $payload = (new OrderPrintPayloadFactory($registry, $snapshotService, $metadataBuilder))
            ->build($context['orderLog']);

        $this->assertSame('templates/strict.docx', $payload['template_path']);
        $this->assertSame('metadata', $payload['context']['render_mode']);
        $this->assertSame(880, $payload['context']['template_version_id']);
        $this->assertSame('snapshot', $payload['context']['template_source']);
    }

    private function createOrderContext(string $orderNo): array
    {
        OrderCategory::query()->create([
            'id' => 9801,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        app(\App\Services\Orders\TemplateAdminService::class)->create([
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
