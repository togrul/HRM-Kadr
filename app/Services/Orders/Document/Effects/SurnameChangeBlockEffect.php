<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;

/**
 * Applies a surname change when the order is approved: records the previous surname
 * and sets the new one from the order's field.
 */
class SurnameChangeBlockEffect implements BlockOrderEffect
{
    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $newSurname = trim((string) ($fields['new_surname'] ?? ''));
        if ($newSurname === '' || $newSurname === $personnel->surname) {
            return;
        }

        $personnel->forceFill([
            'previous_surname' => $personnel->surname,
            'has_changed_initials' => true,
            'initials_changed_date' => optional($order->given_date)->format('Y-m-d'),
            'surname' => $newSurname,
        ])->save();
    }
}
