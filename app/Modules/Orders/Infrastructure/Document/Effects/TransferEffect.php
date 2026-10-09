<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Compensation\Contracts\OrderCompensationSync;
use App\Modules\Personnel\Contracts\GuardsPersonnelChanges;

/**
 * Moves the employee: updates structure and/or position to the new ones chosen on the
 * order (list-bound fields submit the target record id). Before moving, the employee's
 * current structure/position are recorded in the order snapshot so the move can be
 * rolled back if the order is later cancelled. A pay-grade match for the new position
 * also seeds a draft regrade compensation for HR review.
 *
 * Struktur/vəzifə dəyişiklik siyasəti ilə qorunduğu üçün yazma
 * GuardsPersonnelChanges::allowForEffect('transfer') daxilində aparılır — həm tətbiq, həm də
 * ləğv zamanı geri qaytarma (effekt birbaşa çağırılsa da).
 */
class TransferEffect implements OrderEffect
{
    public function __construct(
        private readonly OrderCompensationSync $compensation,
        private readonly GuardsPersonnelChanges $changes,
    ) {}

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

        $this->changes->allowForEffect('transfer', fn (): bool => $personnel->forceFill($update)->save());

        if (! empty($fields['new_position'])) {
            $this->compensation->suggestRegradeFromTransfer($personnel->tabel_no, (int) $fields['new_position'], $order->order_no);
        }
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
            $this->changes->allowForEffect('transfer', fn (): bool => $personnel->forceFill($restore)->save());
        }

        $this->compensation->removeTransferSuggestion($order->order_no);
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
