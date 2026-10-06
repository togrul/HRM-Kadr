<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One domain event waiting to be read by the finance system.
 *
 * Written inside the transaction that produced it, so it cannot describe
 * something that was rolled back.
 *
 * @property string $topic
 * @property string|null $entity_key
 * @property array<string, mixed> $payload
 */
class OutboxEvent extends Model
{
    protected $table = 'integration_outbox';

    /** Only `created_at` — an event is never updated, so `updated_at` would lie. */
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
    ];
}
