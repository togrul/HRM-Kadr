<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Support\Language\AzerbaijaniDateFormatter;

/**
 * Ends employment: sets the personnel's leave_work_date to the termination date.
 */
class TerminationEffect implements OrderEffect
{
    public function __construct(private readonly AzerbaijaniDateFormatter $dates) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $date = $this->dates->parse($fields['date'] ?? null);
        if (! $date) {
            return;
        }

        $personnel->forceFill(['leave_work_date' => $date->format('Y-m-d')])->save();
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        // Re-instate the employee: clear the termination date set on approval.
        $personnel->forceFill(['leave_work_date' => null])->save();
    }
}
