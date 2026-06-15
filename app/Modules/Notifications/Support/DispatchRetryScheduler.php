<?php

namespace App\Modules\Notifications\Support;

use App\Models\NotificationDispatch;
use Illuminate\Support\Carbon;

/**
 * Exponential-backoff retry bookkeeping for notification dispatches.
 *
 * Extracted verbatim from NotificationCampaignDispatcher so the backoff schedule
 * and the dispatch meta it produces can be reasoned about and unit-tested on their
 * own, away from the campaign-dispatch orchestration.
 */
class DispatchRetryScheduler
{
    public function retryBackoffMinutes(int $attemptCount): int
    {
        return match (true) {
            $attemptCount <= 1 => 5,
            $attemptCount === 2 => 15,
            $attemptCount === 3 => 60,
            default => 240,
        };
    }

    public function nextRetryAt(int $attemptCount, ?Carbon $baseTime = null): Carbon
    {
        return ($baseTime ?? now())->copy()->addMinutes($this->retryBackoffMinutes($attemptCount));
    }

    public function failedDispatchMeta(
        ?string $recipientEmail,
        ?string $channel,
        int $attemptCount,
        array $existingMeta = [],
        ?string $driver = null,
    ): array {
        return array_merge($existingMeta, [
            'recipient_email' => $recipientEmail,
            'channel' => $channel,
            'driver' => $driver,
            'retry_after_minutes' => $this->retryBackoffMinutes($attemptCount),
            'next_retry_at' => $this->nextRetryAt($attemptCount)->toIso8601String(),
        ]);
    }

    public function markDispatchMetaAsSent(array $meta): array
    {
        unset($meta['next_retry_at'], $meta['retry_after_minutes']);

        return $meta;
    }

    public function isReadyForRetry(NotificationDispatch $dispatch): bool
    {
        $nextRetryAt = data_get($dispatch->meta, 'next_retry_at');

        if (! $nextRetryAt) {
            return true;
        }

        return Carbon::parse((string) $nextRetryAt)->lessThanOrEqualTo(now());
    }
}
