<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Attendance\Contracts\OrderRestDayWork;
use App\Modules\Orders\Infrastructure\Document\OrderLookupFieldRegistry;
use App\Support\Language\AzerbaijaniDateFormatter;
use Carbon\CarbonImmutable;

/**
 * Calls the employee in to work on a rest day or public holiday: through the Attendance
 * contract the day goes on record as approved rest-day work, with the compensation the
 * order chose (double pay or another day off). Reversal removes that record.
 */
class NonWorkingDayWorkEffect implements OrderEffect
{
    use RemembersEffectState;

    public function __construct(private readonly AzerbaijaniDateFormatter $dates) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $date = $this->dates->parse($fields['work_date'] ?? null);

        if (! $date || blank($personnel->tabel_no) || ! app()->bound(OrderRestDayWork::class)) {
            return;
        }

        $compensation = (int) ($fields['compensation'] ?? 0) === OrderLookupFieldRegistry::REST_DAY_COMPENSATION_DAY_OFF
            ? OrderRestDayWork::COMPENSATION_DAY_OFF
            : OrderRestDayWork::COMPENSATION_DOUBLE_PAY;

        $recordId = app(OrderRestDayWork::class)->record(
            (string) $personnel->tabel_no,
            CarbonImmutable::parse($date->format('Y-m-d')),
            $compensation,
            __('orders::order_composer.effects.non_working_day_work_reason', [
                'number' => $order->order_no,
                'compensation' => __('orders::order_composer.rest_day_compensation.'.$compensation, [], config('app.locale')),
            ], config('app.locale')),
        );

        if ($recordId !== null) {
            $this->rememberState($order, ['rest_day_work_id' => $recordId]);
        }
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $recordId = (int) ($this->rememberedState($order)['rest_day_work_id'] ?? 0);

        if ($recordId > 0 && app()->bound(OrderRestDayWork::class)) {
            app(OrderRestDayWork::class)->remove($recordId);
        }

        $this->forgetState($order, ['rest_day_work_id']);
    }
}
