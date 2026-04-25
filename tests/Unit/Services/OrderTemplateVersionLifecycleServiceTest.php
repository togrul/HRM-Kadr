<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Services\Orders\OrderTemplateVersionLifecycleService;
use App\Services\Orders\TemplatePlaceholderCoverageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrderTemplateVersionLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_sets_single_active_version_deterministically(): void
    {
        $set = $this->makeTemplateSet();
        $v1 = $this->makeVersion($set->id, 1, 'published', true);
        $v2 = $this->makeVersion($set->id, 2, 'draft', false);

        $this->mockCoverage(inspectable: true, missing: []);

        $published = app(OrderTemplateVersionLifecycleService::class)->publishVersion((int) $v2->id);

        $this->assertNotNull($published);
        $this->assertSame((int) $v2->id, (int) $published->id);
        $this->assertTrue((bool) $published->is_active);
        $this->assertSame('published', (string) $published->status);

        $this->assertDatabaseHas('order_template_versions', [
            'id' => $v1->id,
            'is_active' => 0,
        ]);
        $this->assertDatabaseHas('order_template_versions', [
            'id' => $v2->id,
            'is_active' => 1,
            'status' => 'published',
        ]);
        $this->assertSame(
            1,
            OrderTemplateVersion::query()
                ->where('order_template_set_id', (int) $set->id)
                ->where('is_active', true)
                ->count()
        );
    }

    public function test_publish_is_blocked_when_coverage_has_missing_placeholders(): void
    {
        $set = $this->makeTemplateSet();
        $version = $this->makeVersion($set->id, 1, 'draft', false);

        $this->mockCoverage(inspectable: true, missing: ['$fullname']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(__('orders::template_lifecycle.messages.cannot_publish_missing_mappings', [
            'placeholders' => '$fullname',
        ]));

        app(OrderTemplateVersionLifecycleService::class)->publishVersion((int) $version->id);
    }

    public function test_reconcile_activates_latest_published_version_when_none_active(): void
    {
        $set = $this->makeTemplateSet();
        $v1 = $this->makeVersion($set->id, 1, 'draft', false);
        $v2 = $this->makeVersion($set->id, 2, 'published', false);
        $this->makeVersion($set->id, 3, 'draft', false);

        $winner = app(OrderTemplateVersionLifecycleService::class)->reconcileSingleActiveForSet((int) $set->id);

        $this->assertNotNull($winner);
        $this->assertSame((int) $v2->id, (int) $winner->id);
        $this->assertDatabaseHas('order_template_versions', [
            'id' => $v2->id,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('order_template_versions', [
            'id' => $v1->id,
            'is_active' => 0,
        ]);
        $this->assertSame(
            1,
            OrderTemplateVersion::query()
                ->where('order_template_set_id', (int) $set->id)
                ->where('is_active', true)
                ->count()
        );
    }

    private function mockCoverage(bool $inspectable, array $missing): void
    {
        $mock = $this->createMock(TemplatePlaceholderCoverageService::class);
        $mock->method('analyzeForVersion')
            ->willReturn([
                'inspectable' => $inspectable,
                'template_placeholders' => [],
                'mapped_placeholders' => [],
                'missing_placeholders' => $missing,
                'orphan_mappings' => [],
            ]);

        $this->app->instance(TemplatePlaceholderCoverageService::class, $mock);
    }

    private function makeTemplateSet(): OrderTemplateSet
    {
        OrderCategory::query()->create([
            'id' => 9810,
            'name_az' => 'Test',
            'name_en' => 'Test',
            'name_ru' => 'Test',
        ]);

        app(\App\Services\Orders\TemplateAdminService::class)->create([
            'id' => 9811,
            'order_category_id' => 9810,
            'name' => 'Template',
            'content' => 'templates/test.docx',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);

        $orderType = OrderType::query()->create([
            'order_id' => 9811,
            'name' => 'Type A',
        ]);

        return OrderTemplateSet::query()->create([
            'order_type_id' => (int) $orderType->id,
            'name' => 'Set',
        ]);
    }

    private function makeVersion(int $setId, int $versionNo, string $status, bool $active): OrderTemplateVersion
    {
        return OrderTemplateVersion::query()->create([
            'order_template_set_id' => $setId,
            'version_no' => $versionNo,
            'template_name' => "v{$versionNo}",
            'template_path' => "templates/v{$versionNo}.docx",
            'status' => $status,
            'is_active' => $active,
            'published_at' => $active ? now() : null,
        ]);
    }
}
