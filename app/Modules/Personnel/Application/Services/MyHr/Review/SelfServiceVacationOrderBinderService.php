<?php

namespace App\Modules\Personnel\Application\Services\MyHr\Review;

use App\Enums\OrderStatusEnum;
use App\Models\Component;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderLog;
use App\Models\OrderType;
use App\Models\PersonnelVacation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class SelfServiceVacationOrderBinderService
{
    public function bind(PersonnelVacation $vacation, User $reviewer): void
    {
        $vacation->loadMissing([
            'personnel.latestRank.rank',
            'personnel.yearlyVacation',
        ]);

        $personnel = $vacation->personnel;
        $orderType = $this->resolveOrCreateVacationOperationalOrderType();

        if (! $orderType || ! $orderType->order) {
            throw new RuntimeException('Vacation operational order type is not configured.');
        }

        $orderNo = $this->generateOperationalOrderNo('VAC');
        $orderLog = OrderLog::query()->create([
            'order_id' => $orderType->order_id,
            'order_type_id' => $orderType->id,
            'order_no' => $orderNo,
            'given_date' => now()->toDateString(),
            'given_by' => $reviewer->name,
            'given_by_rank' => $reviewer->personnel?->latestRank?->rank?->name ?: 'HR',
            'description' => null,
            'status_id' => OrderStatusEnum::APPROVED->value,
            'creator_id' => $reviewer->id,
        ]);

        $component = $this->resolveOrCreateVacationOperationalComponent($orderType);

        if ($component && $personnel) {
            $rowNumber = 0;

            $orderLog->components()->attach([
                $component->id => ['row_number' => $rowNumber],
            ]);

            $orderLog->attributes()->create([
                'component_id' => $component->id,
                'row_number' => $rowNumber,
                'attributes' => [
                    '$fullname' => ['id' => null, 'value' => $personnel->fullname],
                    '$rank' => ['id' => null, 'value' => $personnel->latestRank?->rank?->name],
                    '$location' => ['id' => null, 'value' => (string) $vacation->vacation_places],
                    '$start_date' => ['id' => null, 'value' => $vacation->start_date?->format('Y-m-d')],
                    '$end_date' => ['id' => null, 'value' => $vacation->end_date?->format('Y-m-d')],
                    '$days' => ['id' => null, 'value' => (int) $vacation->duration],
                ],
            ]);

            $orderLog->personnels()->attach([
                $personnel->tabel_no => ['component_id' => $component->id],
            ]);

            $currentYearlyVacation = $personnel->yearlyVacation
                ->firstWhere('year', (int) $vacation->start_date?->year)
                ?? $personnel->yearlyVacation->first();

            $vacationDaysTotal = (int) ($currentYearlyVacation?->vacation_days_total ?? $vacation->vacation_days_total ?? 0);
            $remainingDaysBefore = (int) ($currentYearlyVacation?->remaining_days ?? $vacation->remaining_days ?? 0);
            $remainingDaysAfter = max(0, $remainingDaysBefore - (int) $vacation->duration);

            if ($currentYearlyVacation) {
                $currentYearlyVacation->forceFill([
                    'remaining_days' => $remainingDaysAfter,
                ])->save();
            }

            $vacation->forceFill([
                'order_no' => $orderNo,
                'order_date' => now(),
                'order_given_by' => $reviewer->name,
                'vacation_days_total' => $vacationDaysTotal,
                'remaining_days' => $remainingDaysAfter,
            ])->save();

            $vacation->refresh();

            return;
        }

        $vacation->forceFill([
            'order_no' => $orderNo,
            'order_date' => now(),
            'order_given_by' => $reviewer->name,
        ])->save();
    }

    private function generateOperationalOrderNo(string $prefix): string
    {
        do {
            $candidate = sprintf('%s-%s', $prefix, Str::upper(Str::random(10)));
        } while (OrderLog::query()->where('order_no', $candidate)->exists());

        return $candidate;
    }

    private function resolveVacationOperationalOrderType(): ?OrderType
    {
        $byBlade = OrderType::query()
            ->with('order')
            ->whereHas('order', fn ($query) => $query->where('blade', Order::BLADE_VACATION))
            ->orderBy('id')
            ->first();

        if ($byBlade) {
            return $byBlade;
        }

        return OrderType::query()
            ->with('order')
            ->whereHas('order', fn ($query) => $query->where('order_model', PersonnelVacation::class))
            ->orderBy('id')
            ->first();
    }

    private function resolveOrCreateVacationOperationalOrderType(): ?OrderType
    {
        $existing = $this->resolveVacationOperationalOrderType();

        if ($existing) {
            return $existing;
        }

        $order = $this->resolveVacationOperationalOrder();

        if (! $order) {
            return null;
        }

        return OrderType::query()->firstOrCreate(
            ['order_id' => $order->id],
            ['name' => $order->name ?: 'Məzuniyyət əmri']
        )->loadMissing('order');
    }

    private function resolveVacationOperationalOrder(): ?Order
    {
        $byBlade = Order::query()
            ->where('blade', Order::BLADE_VACATION)
            ->orderBy('id')
            ->first();

        if ($byBlade) {
            return $byBlade;
        }

        $byModel = Order::query()
            ->where('order_model', PersonnelVacation::class)
            ->orderBy('id')
            ->first();

        if ($byModel) {
            return $byModel;
        }

        $byName = Order::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%məzuniyyət%')
                    ->orWhere('name', 'like', '%mezuniyyet%')
                    ->orWhere('name', 'like', '%vacation%')
                    ->orWhere('content', 'like', '%məzuniyyət%')
                    ->orWhere('content', 'like', '%mezuniyyet%')
                    ->orWhere('content', 'like', '%vacation%');
            })
            ->orderBy('id')
            ->first();

        if ($byName) {
            return $byName;
        }

        $category = $this->resolveOrCreateVacationOrderCategory();
        if (! $category) {
            return null;
        }

        $orderId = $this->nextOrderId();
        $payload = [
            'id' => $orderId,
            'order_category_id' => $category->id,
            'name' => 'Məzuniyyət əmri',
            'content' => 'Auto-generated operational order for self-service vacations.',
            'order_model' => PersonnelVacation::class,
        ];

        if (Schema::hasColumn('orders', 'blade')) {
            $payload['blade'] = Order::BLADE_VACATION;
        }

        DB::table('orders')->insert($payload);

        return Order::query()->find($orderId);
    }

    private function resolveOrCreateVacationOrderCategory(): ?OrderCategory
    {
        $existing = OrderCategory::query()->orderBy('id')->first();

        if ($existing) {
            return $existing;
        }

        return OrderCategory::query()->create([
            'id' => $this->nextOrderCategoryId(),
            'name_az' => 'Ümumi',
            'name_en' => 'General',
            'name_ru' => 'General',
        ]);
    }

    private function nextOrderId(): int
    {
        return ((int) Order::query()->max('id')) + 1;
    }

    private function nextOrderCategoryId(): int
    {
        return ((int) OrderCategory::query()->max('id')) + 1;
    }

    private function resolveOrCreateVacationOperationalComponent(OrderType $orderType): Component
    {
        $usesOrderTypeColumn = Schema::hasColumn('components', 'order_type_id');
        $foreignColumn = $usesOrderTypeColumn ? 'order_type_id' : 'order_id';
        $foreignValue = $usesOrderTypeColumn ? $orderType->id : $orderType->order_id;

        $existing = Component::query()
            ->where($foreignColumn, $foreignValue)
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $payload = [
            $foreignColumn => $foreignValue,
            'rank_id' => null,
            'name' => $orderType->name ?: 'Məzuniyyət',
            'content' => 'Auto-generated self-service vacation component.',
            'dynamic_fields' => null,
        ];

        if (Schema::hasColumn('components', 'title')) {
            $payload['title'] = $orderType->name ?: 'Məzuniyyət';
        }

        $componentId = DB::table('components')->insertGetId($payload);

        return Component::query()->findOrFail($componentId);
    }
}
