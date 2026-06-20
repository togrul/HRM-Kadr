<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Services\Vacation\VacationBalanceService;
use App\Support\Language\AzerbaijaniDateFormatter;

/**
 * Puts the employee on leave: creates the personnel vacation record from the order's
 * fields (start/end/return dates, day count) and deducts the days from the employee's
 * annual vacation balance. Reversal removes the record and restores the balance.
 */
class VacationEffect implements OrderEffect
{
    public function __construct(
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly VacationBalanceService $balance,
    ) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $start = $this->dates->parse($fields['start_date'] ?? null);
        $end = $this->dates->parse($fields['end_date'] ?? null);

        // Fail safe: without a valid period we do not create a partial record.
        if (! $start || ! $end) {
            return;
        }

        $return = $this->dates->parse($fields['return_date'] ?? null) ?? $end->copy()->addDay();
        $days = (int) ($fields['days'] ?? 0);

        $personnel->vacations()->create([
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'return_work_date' => $return->format('Y-m-d'),
            'duration' => $days,
            'vacation_places' => (string) ($fields['location'] ?? ''),
            'order_no' => $order->order_no,
            'order_given_by' => (string) ($order->given_by ?? ''),
            'order_date' => optional($order->given_date)->format('Y-m-d'),
        ]);

        // Deduct the taken days from the employee's annual balance.
        $this->balance->consume($personnel, (int) $start->year, $days);
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        // Remove the leave record this order created (matched by its order number).
        $personnel->vacations()->where('order_no', $order->order_no)->delete();

        // Give the days back to the annual balance.
        $start = $this->dates->parse($fields['start_date'] ?? null);
        $this->balance->release($personnel, (int) ($start?->year ?? now()->year), (int) ($fields['days'] ?? 0));
    }
}
