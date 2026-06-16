<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;

/**
 * Moves a personnel to a new structure and/or position when a transfer order is
 * approved. The order's structured fields carry the target ids (the document still
 * shows their names), so the move is applied directly.
 */
class TransferBlockEffect implements BlockOrderEffect
{
    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $update = [];

        if (! empty($fields['new_structure'])) {
            $update['structure_id'] = (int) $fields['new_structure'];
        }
        if (! empty($fields['new_position'])) {
            $update['position_id'] = (int) $fields['new_position'];
        }

        if ($update !== []) {
            $personnel->forceFill($update)->save();
        }
    }
}
