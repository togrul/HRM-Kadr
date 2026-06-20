<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;

/**
 * Renames the employee: records the previous surname and applies the new one.
 */
class SurnameChangeEffect implements OrderEffect
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

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $newSurname = trim((string) ($fields['new_surname'] ?? ''));
        $previous = trim((string) $personnel->previous_surname);

        // Only roll back if the current surname is the one this order applied and we
        // still hold the prior name to restore.
        if ($previous === '' || ($newSurname !== '' && $newSurname !== $personnel->surname)) {
            return;
        }

        $personnel->forceFill([
            'surname' => $previous,
            'previous_surname' => null,
            'has_changed_initials' => false,
            'initials_changed_date' => null,
        ])->save();
    }
}
