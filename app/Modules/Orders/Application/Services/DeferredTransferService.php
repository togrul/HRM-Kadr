<?php

namespace App\Modules\Orders\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Modules\Orders\Contracts\DeferredTransferApplier;
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
            ->orderBy('template_snapshot->effect_state->effective_date')->orderBy('id')->get(['id']);

        foreach ($orders as $order) {
            try {
                $stats[$this->transfers->applyDue($order) ? 'applied' : 'skipped']++;
            } catch (Throwable $exception) {
                $stats['failed']++;
                Log::error('orders.deferred_transfer.failed', ['order_id' => $order->getKey(), 'error' => $exception->getMessage()]);
            }
        }

        return $stats;
    }
}
