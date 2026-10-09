<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\OrderLog;

/**
 * Keeps what an effect needs to undo itself (ids of the records it created, the values
 * it overwrote) in the order snapshot under `effect_state`, so reverse() works from the
 * order alone — however much later it runs.
 */
trait RemembersEffectState
{
    /**
     * @param  array<string,mixed>  $state
     */
    protected function rememberState(OrderLog $order, array $state): void
    {
        $snapshot = (array) $order->template_snapshot;
        $snapshot['effect_state'] = array_merge((array) ($snapshot['effect_state'] ?? []), $state);
        $order->forceFill(['template_snapshot' => $snapshot])->save();
    }

    /**
     * @return array<string,mixed>
     */
    protected function rememberedState(OrderLog $order): array
    {
        return (array) data_get($order->template_snapshot, 'effect_state', []);
    }

    /**
     * Drop the given keys once reverse() has used them, so a later re-approval starts clean.
     *
     * @param  list<string>  $keys
     */
    protected function forgetState(OrderLog $order, array $keys): void
    {
        $snapshot = (array) $order->template_snapshot;
        $state = (array) ($snapshot['effect_state'] ?? []);

        foreach ($keys as $key) {
            unset($state[$key]);
        }

        $snapshot['effect_state'] = $state;
        $order->forceFill(['template_snapshot' => $snapshot])->save();
    }
}
