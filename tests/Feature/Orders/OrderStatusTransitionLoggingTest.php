<?php

namespace Tests\Feature\Orders;

use App\Models\OrderLog;
use App\Services\Orders\Document\OrderIssueService;
use App\Services\Orders\Document\OrderStatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class OrderStatusTransitionLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_records_a_semantic_orders_activity_entry(): void
    {
        $order = $this->pendingDocxOrder('LOG-1');

        app(OrderStatusTransitionService::class)->approve($order);

        $entry = Activity::query()
            ->where('log_name', 'orders')
            ->where('event', 'approved')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry, 'A domain-level "approved" activity entry should be written.');
        $this->assertSame('LOG-1', $entry->getExtraProperty('order_no'));
        $this->assertSame(10, $entry->getExtraProperty('from_status'));
        $this->assertSame(20, $entry->getExtraProperty('to_status'));
        $this->assertSame('applied', $entry->getExtraProperty('effect'));
    }

    public function test_cancelling_a_pending_order_logs_with_no_effect(): void
    {
        $order = $this->pendingDocxOrder('LOG-2');

        app(OrderStatusTransitionService::class)->cancel($order);

        $entry = Activity::query()
            ->where('log_name', 'orders')
            ->where('event', 'cancelled')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('none', $entry->getExtraProperty('effect'));
    }

    private function pendingDocxOrder(string $orderNo): OrderLog
    {
        // An empty snapshot means applyEffect/reverseEffect find no template and no-op,
        // so the transition + its audit entry run without needing the full effect graph.
        return OrderLog::query()->create([
            'order_no' => $orderNo,
            'order_type_id' => 3010,
            'given_date' => '2026-01-01 00:00:00',
            'given_by' => 'Test',
            'given_by_rank' => 'Test',
            'creator_id' => \App\Models\User::factory()->create()->id,
            'status_id' => OrderIssueService::STATUS_PENDING,
            'template_render_mode' => OrderIssueService::RENDER_MODE_DOCX,
            'template_snapshot' => [],
        ]);
    }
}
