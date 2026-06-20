<?php

namespace Tests\Unit\Modules\Notifications;

use App\Models\NotificationDispatch;
use App\Modules\Notifications\Support\DispatchRetryScheduler;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DispatchRetrySchedulerTest extends TestCase
{
    private DispatchRetryScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new DispatchRetryScheduler;
    }

    public function test_backoff_grows_with_attempt_count(): void
    {
        $this->assertSame(5, $this->scheduler->retryBackoffMinutes(0));
        $this->assertSame(5, $this->scheduler->retryBackoffMinutes(1));
        $this->assertSame(15, $this->scheduler->retryBackoffMinutes(2));
        $this->assertSame(60, $this->scheduler->retryBackoffMinutes(3));
        $this->assertSame(240, $this->scheduler->retryBackoffMinutes(4));
        $this->assertSame(240, $this->scheduler->retryBackoffMinutes(10));
    }

    public function test_next_retry_at_adds_backoff_to_base_time(): void
    {
        $base = Carbon::parse('2026-06-16 10:00:00');

        $this->assertTrue(
            $this->scheduler->nextRetryAt(2, $base)->equalTo($base->copy()->addMinutes(15))
        );
    }

    public function test_failed_meta_carries_retry_fields_and_preserves_existing(): void
    {
        $meta = $this->scheduler->failedDispatchMeta('a@b.az', 'mail', 1, ['keep' => 'yes'], 'smtp');

        $this->assertSame('a@b.az', $meta['recipient_email']);
        $this->assertSame('mail', $meta['channel']);
        $this->assertSame('smtp', $meta['driver']);
        $this->assertSame(5, $meta['retry_after_minutes']);
        $this->assertArrayHasKey('next_retry_at', $meta);
        $this->assertSame('yes', $meta['keep']);
    }

    public function test_marking_sent_strips_retry_fields(): void
    {
        $meta = $this->scheduler->markDispatchMetaAsSent([
            'recipient_email' => 'a@b.az',
            'next_retry_at' => '2026-06-16T10:15:00+00:00',
            'retry_after_minutes' => 15,
        ]);

        $this->assertArrayNotHasKey('next_retry_at', $meta);
        $this->assertArrayNotHasKey('retry_after_minutes', $meta);
        $this->assertSame('a@b.az', $meta['recipient_email']);
    }

    public function test_ready_for_retry_respects_next_retry_at(): void
    {
        $noSchedule = new NotificationDispatch(['meta' => []]);
        $this->assertTrue($this->scheduler->isReadyForRetry($noSchedule));

        $past = new NotificationDispatch(['meta' => ['next_retry_at' => now()->subMinute()->toIso8601String()]]);
        $this->assertTrue($this->scheduler->isReadyForRetry($past));

        $future = new NotificationDispatch(['meta' => ['next_retry_at' => now()->addHour()->toIso8601String()]]);
        $this->assertFalse($this->scheduler->isReadyForRetry($future));
    }
}
