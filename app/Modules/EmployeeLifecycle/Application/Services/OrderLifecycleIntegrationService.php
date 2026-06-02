<?php

namespace App\Modules\EmployeeLifecycle\Application\Services;

use App\Helpers\UsefulHelpers;
use App\Models\OrderLog;
use App\Models\Personnel;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderLifecycleIntegrationService
{
    public function handleApprovedOrder(OrderLog $orderLog, ?int $actorId = null): void
    {
        if (! Schema::hasTable('employee_lifecycle_events')) {
            return;
        }

        $orderId = (int) $orderLog->order_id;

        if ($this->configured('offboarding_order_ids', $orderId)) {
            $this->createOffboardingFromOrder($orderLog, $actorId);

            return;
        }

        if ($this->configured('movement_order_ids', $orderId)
            || $this->configured('promotion_order_ids', $orderId)
            || $this->configured('transfer_order_ids', $orderId)) {
            $this->createMovementFromOrder($orderLog, $actorId);
        }
    }

    private function createMovementFromOrder(OrderLog $orderLog, ?int $actorId): void
    {
        $orderLog->loadMissing(['personnels', 'attributes']);

        foreach ($orderLog->personnels as $personnel) {
            $attributes = $this->attributesForPersonnel($orderLog, $personnel);
            $targetStructureId = $this->intFromAttributes($attributes, [
                '$target_structure_id', '$to_structure_id', '$new_structure_id', '$structure_id', '$structure',
            ]);
            $targetPositionId = $this->intFromAttributes($attributes, [
                '$target_position_id', '$to_position_id', '$new_position_id', '$position_id', '$position',
            ]);

            if ($targetStructureId === null && $targetPositionId === null) {
                continue;
            }

            if ($this->sourceExists('order_log_movement', $orderLog->id, $personnel->id)) {
                continue;
            }

            $movementId = app(LifecyclePlanTemplateService::class)->scheduleMovement(
                personnelId: (int) $personnel->id,
                movementType: $this->movementType((int) $orderLog->order_id),
                targetStructureId: $targetStructureId,
                targetPositionId: $targetPositionId,
                effectiveDate: $this->dateFromAttributes($attributes, $orderLog->given_date),
                reason: $orderLog->description['description'] ?? __('employee-lifecycle::dashboard.labels.order_source'),
                ownerUserId: $actorId ?: $orderLog->creator_id,
                createdBy: $actorId ?: $orderLog->creator_id,
            );

            $eventId = DB::table('employee_lifecycle_movements')->where('id', $movementId)->value('event_id');
            $this->markOrderSource((int) $eventId, 'order_log_movement', $orderLog, $personnel, $attributes);
        }
    }

    private function createOffboardingFromOrder(OrderLog $orderLog, ?int $actorId): void
    {
        $orderLog->loadMissing(['personnels', 'attributes']);

        foreach ($orderLog->personnels as $personnel) {
            if ($this->sourceExists('order_log_offboarding', $orderLog->id, $personnel->id)) {
                continue;
            }

            $attributes = $this->attributesForPersonnel($orderLog, $personnel);
            $caseId = app(LifecyclePlanTemplateService::class)->openOffboardingCase(
                personnelId: (int) $personnel->id,
                lastWorkingDate: $this->dateFromAttributes($attributes, $orderLog->given_date),
                reason: $orderLog->description['description'] ?? __('employee-lifecycle::dashboard.labels.order_source'),
                ownerUserId: $actorId ?: $orderLog->creator_id,
                createdBy: $actorId ?: $orderLog->creator_id,
            );

            $eventId = DB::table('employee_lifecycle_offboarding_cases')->where('id', $caseId)->value('event_id');
            $this->markOrderSource((int) $eventId, 'order_log_offboarding', $orderLog, $personnel, $attributes);
        }
    }

    private function sourceExists(string $sourceType, int $orderLogId, int $personnelId): bool
    {
        return DB::table('employee_lifecycle_events')
            ->where('source_type', $sourceType)
            ->where('source_id', $orderLogId)
            ->where('personnel_id', $personnelId)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesForPersonnel(OrderLog $orderLog, Personnel $personnel): array
    {
        $componentId = DB::table('order_log_personnels')
            ->where('order_no', $orderLog->order_no)
            ->where('tabel_no', $personnel->tabel_no)
            ->value('component_id');

        $attributes = $orderLog->attributes
            ->firstWhere('component_id', $componentId)
            ?->attributes;

        if (is_array($attributes)) {
            return $attributes;
        }

        return $orderLog->attributes
            ->pluck('attributes')
            ->first(fn ($row): bool => is_array($row) && data_get($row, '$fullname.value') === $personnel->fullname) ?: [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $keys
     */
    private function intFromAttributes(array $attributes, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = data_get($attributes, "{$key}.id", data_get($attributes, "{$key}.value", data_get($attributes, $key)));

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function dateFromAttributes(array $attributes, CarbonInterface|string|null $fallback): Carbon
    {
        $date = data_get($attributes, '$effective_date.value')
            ?: data_get($attributes, '$leave_work_date.value')
            ?: data_get($attributes, '$last_working_date.value')
            ?: data_get($attributes, '$date.value');

        if ($date) {
            return Carbon::parse($date);
        }

        $day = data_get($attributes, '$day.value');
        $monthLabel = data_get($attributes, '$month.value');
        $year = data_get($attributes, '$year.value');
        $month = $monthLabel ? UsefulHelpers::convertToMonthNumber((string) $monthLabel, config('app.locale')) : null;

        if ($day && $month && $year) {
            return Carbon::parse("{$year}-{$month}-{$day}");
        }

        return $fallback instanceof CarbonInterface ? $fallback->copy() : Carbon::parse($fallback ?: today());
    }

    private function movementType(int $orderId): string
    {
        if ($this->configured('promotion_order_ids', $orderId)) {
            return 'promotion';
        }

        if ($this->configured('transfer_order_ids', $orderId)) {
            return 'transfer';
        }

        return 'role_change';
    }

    private function configured(string $key, int $orderId): bool
    {
        return in_array($orderId, array_map('intval', (array) config("employee_lifecycle.order_integration.{$key}", [])), true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function markOrderSource(int $eventId, string $sourceType, OrderLog $orderLog, Personnel $personnel, array $attributes): void
    {
        DB::table('employee_lifecycle_events')->where('id', $eventId)->update([
            'source_type' => $sourceType,
            'source_id' => $orderLog->id,
            'meta' => json_encode([
                'order_log_id' => $orderLog->id,
                'order_no' => $orderLog->order_no,
                'order_id' => $orderLog->order_id,
                'personnel_id' => $personnel->id,
                'tabel_no' => $personnel->tabel_no,
                'attributes' => $attributes,
            ]),
            'updated_at' => now(),
        ]);
    }
}
