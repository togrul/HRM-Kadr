<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Support\Language\AzerbaijaniDateFormatter;

/**
 * Sends the employee on a business trip (ezamiyyət): approving the order creates the
 * trip in the BusinessTrips register (destination, period, purpose; transport and
 * per-diem kept alongside), keyed by the order number. Revoking the approval removes
 * that trip again — it never happened.
 */
class BusinessTripEffect implements OrderEffect
{
    public function __construct(private readonly AzerbaijaniDateFormatter $dates) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $start = $this->dates->parse($fields['start_date'] ?? null);
        $end = $this->dates->parse($fields['end_date'] ?? null);

        // Fail safe: without a valid period we do not create a partial record.
        if (! $start || ! $end || blank($personnel->tabel_no)) {
            return;
        }

        $return = $this->dates->parse($fields['return_date'] ?? null);

        PersonnelBusinessTrip::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'location' => trim((string) ($fields['location'] ?? '')),
            'description' => trim((string) ($fields['purpose'] ?? '')),
            'attributes' => array_filter([
                'return_date' => $return?->format('Y-m-d'),
                'transport' => trim((string) ($fields['transport'] ?? '')),
                'per_diem' => trim((string) ($fields['per_diem'] ?? '')),
            ], fn (?string $value): bool => filled($value)),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'order_no' => $order->order_no,
            'order_given_by' => (string) ($order->given_by ?? ''),
            'order_date' => optional($order->given_date)->format('Y-m-d'),
        ]);
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        PersonnelBusinessTrip::withTrashed()
            ->where('tabel_no', $personnel->tabel_no)
            ->where('order_no', $order->order_no)
            ->forceDelete();
    }
}
