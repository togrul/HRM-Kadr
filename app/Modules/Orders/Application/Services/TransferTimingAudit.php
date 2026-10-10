<?php

namespace App\Modules\Orders\Application\Services;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class TransferTimingAudit
{
    /** @return Generator<int, array<string, mixed>> */
    public function findings(?int $orderId = null): Generator
    {
        $orders = OrderLog::query()->withTrashed()
            ->where('status_id', OrderStatusEnum::APPROVED->value)
            ->whereNotNull('template_snapshot')
            ->when($orderId !== null, fn (Builder $query): Builder => $query->whereKey($orderId))
            ->select(['id', 'order_no', 'given_date', 'deleted_at', 'template_snapshot'])
            ->lazyById(200);

        foreach ($orders as $order) {
            $snapshot = (array) $order->template_snapshot;
            $state = (array) ($snapshot['effect_state'] ?? []);
            if (! array_key_exists('prev_position_id', $state) && ! array_key_exists('prev_structure_id', $state)) {
                continue;
            }

            $appliedAt = $state['transfer_applied_at'] ?? null;
            if (! empty($state['transfer_pending']) && $appliedAt === null) {
                continue;
            }

            $effectiveDate = $state['effective_date'] ?? $order->getRawOriginal('given_date');
            $effective = $this->date($effectiveDate);
            $applied = $this->timestamp($appliedAt);
            $classification = match (true) {
                $effective === null => 'invalid_effective_date',
                ! empty($state['transfer_pending']) => 'inconsistent_pending_state',
                $appliedAt !== null && $applied === null => 'invalid_application_timestamp',
                $applied === null => 'legacy_requires_history_review',
                $applied->startOfDay()->lessThan($effective) => 'confirmed_early_application',
                default => null,
            };

            if ($classification === null) {
                continue;
            }

            yield [
                'order_id' => (int) $order->id,
                'order_no' => (string) $order->order_no,
                'classification' => $classification,
                'personnel_id' => $state['transfer_personnel_id'] ?? $snapshot['personnel_id'] ?? null,
                'effective_date' => $effectiveDate,
                'transfer_applied_at' => $appliedAt,
                'previous_structure_id' => $state['prev_structure_id'] ?? null,
                'previous_position_id' => $state['prev_position_id'] ?? null,
                'transfer_target' => $state['transfer_target'] ?? null,
                'deleted_at' => $order->getRawOriginal('deleted_at'),
            ];
        }
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone'));

            return $date && $date->toDateString() === $value ? $date : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function timestamp(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})?$/D', $value)
            || $this->date(substr($value, 0, 10)) === null
            || (int) substr($value, 11, 2) > 23 || (int) substr($value, 14, 2) > 59 || (int) substr($value, 17, 2) > 59) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, config('app.timezone'))->setTimezone(config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }
}
