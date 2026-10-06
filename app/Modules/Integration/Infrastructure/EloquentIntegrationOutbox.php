<?php

namespace App\Modules\Integration\Infrastructure;

use App\Models\OutboxEvent;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;

class EloquentIntegrationOutbox implements IntegrationOutbox
{
    public function record(string $topic, ?string $entityKey, array $payload): void
    {
        OutboxEvent::query()->create([
            'topic' => $topic,
            'entity_key' => $entityKey,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
