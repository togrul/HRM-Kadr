<?php

namespace App\Modules\Integration\Application\Services;

use App\Models\OutboxEvent;
use App\Modules\Integration\Support\Contract;

/**
 * The order feed — approvals and reversals, in the order they happened.
 *
 * ## This one really is a change feed
 *
 * Unlike the people and org feeds, which sweep a table, this reads the outbox:
 * every row is an event that already occurred, written inside the transaction
 * that produced it. The cursor is the outbox id, so a reader that stops halfway
 * resumes exactly where it left off and never re-applies what it already has.
 *
 * ## Reversals travel as their own event
 *
 * An approval and a later reversal are two rows, not an edit of one. The
 * counterpart therefore sees the sequence rather than only the end state — which
 * is what an auditor needs when asked why a payroll figure changed.
 */
class OrderFeedService
{
    /**
     * @return array{items: list<array<string, mixed>>, last_sequence: int, has_more: bool}
     */
    public function page(int $after = 0, int $limit = Contract::DEFAULT_LIMIT): array
    {
        $limit = max(1, min($limit, Contract::MAX_LIMIT));

        $events = OutboxEvent::query()
            ->where('topic', Contract::ORDERS)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $events->count() > $limit;
        $events = $events->take($limit);

        return [
            'items' => $events->map(fn (OutboxEvent $event): array => array_merge(
                $event->payload,
                // The sequence belongs to the envelope, not the event body: the
                // reader needs it to advance its cursor even for a row it skips.
                ['sequence' => (int) $event->id],
            ))->values()->all(),
            'last_sequence' => (int) ($events->last()->id ?? $after),
            'has_more' => $hasMore,
        ];
    }
}
