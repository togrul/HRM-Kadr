<?php

namespace App\Modules\Orders\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderParticipant;
use App\Modules\Orders\Contracts\DeferredTransferApplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeferredTransferService
{
    public function __construct(private readonly DeferredTransferApplier $transfers) {}

    /** @return array{applied: int, skipped: int, failed: int} */
    public function applyDue(): array
    {
        $stats = ['applied' => 0, 'skipped' => 0, 'failed' => 0];
        $orders = OrderLog::query()->where('status_id', OrderStatusEnum::APPROVED->value)
            ->where('template_snapshot->effect_state->transfer_pending', true)
            ->where('template_snapshot->effect_state->effective_date', '<=', today()->toDateString())
            ->get(['id', 'template_snapshot'])->map(fn (OrderLog $order): array => ['order_id' => (int) $order->id, 'participant_id' => null, 'on' => data_get($order->template_snapshot, 'effect_state.effective_date')])->all();
        $participants = OrderParticipant::query()->whereHas('order', fn (Builder $query): Builder => $query->where('status_id', OrderStatusEnum::APPROVED->value))
            ->where('effect_state->transfer_pending', true)
            ->where('effect_state->effective_date', '<=', today()->toDateString())->get(['id', 'order_log_id', 'effect_state']);
        foreach ($participants as $participant) {
            $orders[] = ['order_id' => (int) $participant->order_log_id, 'participant_id' => (int) $participant->id, 'on' => $participant->effect_state['effective_date']];
        }
        usort($orders, fn (array $a, array $b): int => [$a['on'], $a['order_id'], $a['participant_id'] ?? 0] <=> [$b['on'], $b['order_id'], $b['participant_id'] ?? 0]);

        foreach ($orders as $candidate) {
            try {
                $order = OrderLog::query()->find($candidate['order_id']);
                $stats[$order !== null && $this->transfers->applyDue($order, $candidate['participant_id']) ? 'applied' : 'skipped']++;
            } catch (Throwable $exception) {
                $stats['failed']++;
                Log::error('orders.deferred_transfer.failed', ['order_id' => $candidate['order_id'], 'participant_id' => $candidate['participant_id'], 'error' => $exception->getMessage()]);
            }
        }

        return $stats;
    }
}
