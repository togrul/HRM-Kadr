<?php

namespace App\Modules\Integration\Infrastructure;

use App\Models\OutboxEvent;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EloquentIntegrationOutbox implements IntegrationOutbox
{
    public function __construct(private readonly ?OutboxSequencer $sequencer = null) {}

    /**
     * The row is written inside the caller's transaction; its feed sequence is assigned
     * once that transaction committed (OutboxSequencer). A failed assignment never fails the
     * already committed business change — the next write or feed read assigns it.
     */
    public function record(string $topic, ?string $entityKey, array $payload): void
    {
        OutboxEvent::query()->create([
            'topic' => $topic,
            'entity_key' => $entityKey,
            'payload' => $payload,
            'created_at' => now(),
        ]);

        DB::afterCommit(function (): void {
            try {
                ($this->sequencer ?? app(OutboxSequencer::class))->assignPending();
            } catch (Throwable $exception) {
                Log::warning('integration.outbox.sequence_failed', ['error' => $exception->getMessage()]);
            }
        });
    }
}
