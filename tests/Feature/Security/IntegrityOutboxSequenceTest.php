<?php

use App\Models\OutboxEvent;
use App\Modules\Integration\Application\Services\OrderFeedService;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;
use App\Modules\Integration\Support\Contract;
use Illuminate\Support\Facades\DB;

/*
 * M9: kursor commit sırası ilə verilən `sequence`-dir. Uzun tranzaksiyada yazılmış kiçik id
 * oxucu onu keçəndən sonra commit olunsa da, hadisə itmir — ona növbəti sequence verilir.
 */

it('does not skip an event whose lower id committed after the reader passed it', function (): void {
    $outbox = app(IntegrationOutbox::class);
    $feed = app(OrderFeedService::class);

    // The long transaction took id 1 first but has not committed yet; a short one commits id 2.
    DB::table('integration_outbox')->insert(['id' => 2, 'topic' => Contract::ORDERS, 'entity_key' => 'B', 'payload' => json_encode(['order_no' => 'B']), 'created_at' => now()]);
    app(App\Modules\Integration\Infrastructure\OutboxSequencer::class)->assignPending();

    $first = $feed->page(0);
    expect(array_column($first['items'], 'order_no'))->toBe(['B']);

    // Now the long transaction commits row id 1 (its after-commit numbering not run yet).
    DB::table('integration_outbox')->insert(['id' => 1, 'topic' => Contract::ORDERS, 'entity_key' => 'A', 'payload' => json_encode(['order_no' => 'A']), 'created_at' => now()]);

    $second = $feed->page($first['last_sequence']);
    expect(array_column($second['items'], 'order_no'))->toBe(['A'])
        ->and($second['last_sequence'])->toBeGreaterThan($first['last_sequence']);

    // Written through the outbox, an event is numbered once its transaction commits.
    DB::transaction(fn () => $outbox->record(Contract::ORDERS, 'C', ['order_no' => 'C']));
    expect(OutboxEvent::query()->where('entity_key', 'C')->value('sequence'))->not->toBeNull()
        ->and(array_column($feed->page($second['last_sequence'])['items'], 'order_no'))->toBe(['C'])
        ->and(OutboxEvent::query()->whereNull('sequence')->count())->toBe(0);
});
