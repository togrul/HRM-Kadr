<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;

/**
 * Moves the employee: updates structure and/or position to the new ones chosen on the
 * order (list-bound fields submit the target record id). Before moving, the employee's
 * current structure/position are recorded in the order snapshot so the move can be
 * rolled back if the order is later cancelled.
 */
class TransferEffect implements OrderEffect
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

        if ($update === []) {
            return;
        }

        // Remember where the employee was, so reverse() can put them back.
        $this->rememberPreState($order, [
            'prev_structure_id' => $personnel->structure_id,
            'prev_position_id' => $personnel->position_id,
        ]);

        $personnel->forceFill($update)->save();
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $state = (array) data_get($order->template_snapshot, 'effect_state', []);

        $restore = [];
        if (array_key_exists('prev_structure_id', $state) && ! empty($fields['new_structure'])) {
            $restore['structure_id'] = $state['prev_structure_id'] !== null ? (int) $state['prev_structure_id'] : null;
        }
        if (array_key_exists('prev_position_id', $state) && ! empty($fields['new_position'])) {
            $restore['position_id'] = $state['prev_position_id'] !== null ? (int) $state['prev_position_id'] : null;
        }

        if ($restore !== []) {
            $personnel->forceFill($restore)->save();
        }
    }

    /**
     * Persist before-state into the order snapshot under `effect_state`.
     *
     * @param  array<string,mixed>  $state
     */
    private function rememberPreState(OrderLog $order, array $state): void
    {
        $snapshot = (array) $order->template_snapshot;
        $snapshot['effect_state'] = array_merge((array) ($snapshot['effect_state'] ?? []), $state);
        $order->forceFill(['template_snapshot' => $snapshot])->save();
    }
}
