<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Support\Language\AzerbaijaniDateFormatter;

/**
 * Creates the personnel vacation record for an approved leave order, from the block
 * order's own fields (start/end/return dates, day count). Dates are parsed from
 * whatever form the field carries (ISO, DD.MM.YYYY, or the Azerbaijani long form).
 *
 * Note: vacation-balance accounting (vacation_days_total / remaining_days) is left
 * to the existing balance logic and intentionally not duplicated here.
 */
class VacationBlockEffect implements BlockOrderEffect
{
    public function __construct(private readonly AzerbaijaniDateFormatter $dates) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $start = $this->dates->parse($fields['start_date'] ?? null);
        $end = $this->dates->parse($fields['end_date'] ?? null);

        // Fail safe: without a valid period we do not create a partial record.
        if (! $start || ! $end) {
            return;
        }

        $return = $this->dates->parse($fields['return_date'] ?? null) ?? $end->copy()->addDay();

        $personnel->vacations()->create([
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'return_work_date' => $return->format('Y-m-d'),
            'duration' => (int) ($fields['days'] ?? 0),
            'vacation_places' => (string) ($fields['location'] ?? ''),
            'order_no' => $order->order_no,
            'order_date' => optional($order->given_date)->format('Y-m-d'),
            'order_given_by' => trim((string) $order->given_by_rank.' '.(string) $order->given_by),
        ]);
    }
}
