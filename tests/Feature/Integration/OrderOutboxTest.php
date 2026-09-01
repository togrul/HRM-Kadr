<?php

namespace Tests\Feature\Integration;

use App\Models\ApiToken;
use App\Models\OutboxEvent;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;
use App\Modules\Integration\Infrastructure\EloquentIntegrationOutbox;
use App\Modules\Integration\Infrastructure\NullIntegrationOutbox;
use App\Modules\Integration\Support\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Tests\TestCase;

/**
 * The transactional outbox behind the order feed.
 *
 * The property being protected is narrow but decisive: an event must not survive
 * a transaction that rolled back. If it did, the finance system would hold a
 * fact that never happened — an approved order that was actually refused — and
 * nothing downstream would ever correct it, because there is no second event to
 * say so.
 */
class OrderOutboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('integration-ip:127.0.0.1');
    }

    /** An event written in a transaction that fails must vanish with it. */
    public function test_an_event_does_not_survive_a_rolled_back_transaction(): void
    {
        $outbox = app(IntegrationOutbox::class);

        try {
            DB::transaction(function () use ($outbox): void {
                $outbox->record(Contract::ORDERS, 'EM-1', ['order_no' => 'EM-1', 'status' => 'approved']);

                throw new RuntimeException('effect failed after the event was recorded');
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(0, OutboxEvent::query()->count(), 'Baş verməmiş fakt qalmamalıdır.');
    }

    /** A committed transaction keeps its event. */
    public function test_a_committed_transaction_keeps_its_event(): void
    {
        DB::transaction(function (): void {
            app(IntegrationOutbox::class)->record(Contract::ORDERS, 'EM-1', ['order_no' => 'EM-1']);
        });

        $this->assertSame(1, OutboxEvent::query()->count());
    }

    /**
     * With the module enabled the real writer is bound.
     *
     * The Orders engine always records; only the binding decides whether that
     * means anything. Asserting the wiring keeps a standalone installation from
     * silently accumulating rows nobody reads — and, more importantly, keeps the
     * dependency resolvable so the engine still boots.
     */
    public function test_the_binding_follows_the_module(): void
    {
        config(['modules.catalog.integration.enabled' => true]);

        $this->assertInstanceOf(EloquentIntegrationOutbox::class, app(IntegrationOutbox::class));
    }

    /** The no-op default exists even without the module. */
    public function test_a_null_writer_is_available_for_standalone(): void
    {
        $null = new NullIntegrationOutbox;
        $null->record(Contract::ORDERS, 'EM-1', ['order_no' => 'EM-1']);

        $this->assertSame(0, OutboxEvent::query()->count());
    }

    /** The feed walks events by cursor, oldest first. */
    public function test_the_feed_pages_events_by_cursor(): void
    {
        $this->event('EM-1', 'approved');
        $this->event('EM-2', 'approved');

        $token = ApiToken::generate('ARBAY', null)['plain'];

        $first = $this->withToken($token)->getJson('/api/v1/orders?limit=1')->assertOk()->json('data');

        $this->assertCount(1, $first['items']);
        $this->assertSame('EM-1', $first['items'][0]['order_no']);
        $this->assertTrue($first['has_more']);

        $next = $this->withToken($token)
            ->getJson('/api/v1/orders?after='.$first['last_sequence'])->json('data');

        $this->assertSame('EM-2', $next['items'][0]['order_no']);
        $this->assertFalse($next['has_more']);
    }

    /**
     * A reversal is its own event, not an edit of the approval.
     *
     * The counterpart therefore sees the sequence rather than only the end state
     * — which is what an auditor needs when asked why a payroll figure changed.
     */
    public function test_a_reversal_arrives_as_a_second_event(): void
    {
        $this->event('EM-1', 'approved');
        $this->event('EM-1', 'reversed');

        $items = $this->withToken(ApiToken::generate('ARBAY', null)['plain'])
            ->getJson('/api/v1/orders')->json('data.items');

        $this->assertSame(['approved', 'reversed'], array_column($items, 'status'));
        $this->assertSame([1, 2], array_column($items, 'sequence'));
    }

    /** The orders feed needs its own ability. */
    public function test_the_orders_feed_is_scoped(): void
    {
        $orgOnly = ApiToken::generate('Struktur', [Contract::ABILITY_ORG])['plain'];

        $this->withToken($orgOnly)->getJson('/api/v1/orders')->assertForbidden();
    }

    private function event(string $orderNo, string $status): void
    {
        app(IntegrationOutbox::class)->record(Contract::ORDERS, $orderNo, [
            'external_id' => $orderNo,
            'order_no' => $orderNo,
            'effect' => 'vacation',
            'status' => $status,
            'reversible' => true,
        ]);
    }
}
