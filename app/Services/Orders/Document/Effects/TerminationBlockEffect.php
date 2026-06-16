<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Support\Language\AzerbaijaniDateFormatter;

/**
 * Ends a personnel's employment when a termination order is approved: stamps the
 * leave-work date so the person drops out of the active roster (scopeActive filters
 * on leave_work_date). The date comes from the order's own field.
 */
class TerminationBlockEffect implements BlockOrderEffect
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
}
