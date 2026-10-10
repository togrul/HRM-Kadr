<?php

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Modules\Orders\Application\Services\TransferTimingAudit;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    foreach ([10, 20, 30] as $id) {
        OrderStatus::query()->firstOrCreate(['id' => $id], ['locale' => 'az', 'name' => 'Test']);
    }
    config(['app.timezone' => 'Asia/Baku']);
});

function timingAuditOrder(array $state, int $status = 20): OrderLog
{
    return OrderLog::query()->create([
        'order_no' => 'AUDIT-'.OrderLog::withTrashed()->count(),
        'status_id' => $status,
        'given_date' => '2026-10-01',
        'given_by' => 'Test',
        'given_by_rank' => '',
        'template_snapshot' => ['personnel_id' => 12, 'effect_state' => ['prev_position_id' => 4, 'effective_date' => '2026-10-20', ...$state]],
    ]);
}

it('reports legacy evidence without claiming that early application is proven', function (): void {
    $order = timingAuditOrder([]);
    $findings = iterator_to_array(app(TransferTimingAudit::class)->findings());

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['classification'])->toBe('legacy_requires_history_review')
        ->and($findings[0]['order_id'])->toBe($order->id)
        ->and($findings[0]['previous_position_id'])->toBe(4)
        ->and($findings[0]['personnel_id'])->toBe(12);
});

it('uses recorded application time for historical early application and excludes on-time transfers', function (): void {
    $early = timingAuditOrder(['effective_date' => '2025-01-10', 'transfer_applied_at' => '2025-01-09T19:00:00Z']);
    timingAuditOrder(['effective_date' => '2025-01-10', 'transfer_applied_at' => '2025-01-09T20:00:00Z']);
    timingAuditOrder(['effective_date' => '2025-01-10', 'transfer_applied_at' => '2025-01-11 10:00:00']);

    $findings = iterator_to_array(app(TransferTimingAudit::class)->findings());
    expect($findings)->toHaveCount(1)
        ->and($findings[0]['order_id'])->toBe($early->id)
        ->and($findings[0]['classification'])->toBe('confirmed_early_application');
});

it('skips clean pending transfers and reports contradictory pending snapshots', function (): void {
    timingAuditOrder(['transfer_pending' => true, 'transfer_applied_at' => null]);
    $inconsistent = timingAuditOrder(['transfer_pending' => true, 'transfer_applied_at' => '2026-10-01T10:00:00+04:00']);
    $findings = iterator_to_array(app(TransferTimingAudit::class)->findings());
    expect($findings)->toHaveCount(1)
        ->and($findings[0]['order_id'])->toBe($inconsistent->id)
        ->and($findings[0]['classification'])->toBe('inconsistent_pending_state');
});

it('includes soft-deleted approved orders but excludes cancelled and draft orders', function (): void {
    $deleted = timingAuditOrder([]);
    $deleted->delete();
    timingAuditOrder([], OrderStatusEnum::PENDING->value);
    timingAuditOrder([], OrderStatusEnum::CANCELLED->value);
    $findings = iterator_to_array(app(TransferTimingAudit::class)->findings());
    expect($findings)->toHaveCount(1)->and($findings[0]['deleted_at'])->not->toBeNull();
});

it('reports malformed dates and timestamps without aborting later records', function (): void {
    timingAuditOrder(['effective_date' => '2026-02-30']);
    timingAuditOrder(['transfer_applied_at' => 'tomorrow']);
    timingAuditOrder(['transfer_applied_at' => '2026-10-01 25:00:00']);
    timingAuditOrder([]);
    expect(array_column(iterator_to_array(app(TransferTimingAudit::class)->findings()), 'classification'))->toBe([
        'invalid_effective_date', 'invalid_application_timestamp', 'invalid_application_timestamp', 'legacy_requires_history_review',
    ]);
});

it('falls back to the order date for legacy snapshots without an effective date', function (): void {
    $order = timingAuditOrder([]);
    $snapshot = $order->template_snapshot;
    unset($snapshot['effect_state']['effective_date']);
    $order->update(['template_snapshot' => $snapshot]);
    $findings = iterator_to_array(app(TransferTimingAudit::class)->findings());
    expect($findings[0]['effective_date'])->toBe('2026-10-01');
});

it('streams findings and a summary without issuing any database writes', function (): void {
    $order = timingAuditOrder([]);
    timingAuditOrder([]);
    $before = $order->getRawOriginal('template_snapshot');
    DB::enableQueryLog();
    Artisan::call('orders:audit-transfer-timing', ['--order-id' => $order->id]);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    $lines = array_map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR), explode("\n", trim(Artisan::output())));
    expect($lines)->toHaveCount(2)
        ->and($lines[0]['order_id'])->toBe($order->id)
        ->and($lines[1])->toMatchArray(['type' => 'summary', 'read_only' => true, 'count' => 1])
        ->and($order->fresh()->getRawOriginal('template_snapshot'))->toBe($before);
    foreach ($queries as $query) {
        expect(preg_match('/^\s*(insert|update|delete|replace|alter|create|drop)\b/i', $query['query']))->toBe(0);
    }
});

it('rejects invalid order filters and emits an empty summary for an unknown order', function (): void {
    $this->artisan('orders:audit-transfer-timing', ['--order-id' => '-1'])->assertExitCode(2);
    Artisan::call('orders:audit-transfer-timing', ['--order-id' => 99999]);
    expect(json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR))->toMatchArray(['type' => 'summary', 'count' => 0]);
});
