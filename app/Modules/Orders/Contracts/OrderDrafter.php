<?php

namespace App\Modules\Orders\Contracts;

use App\Models\OrderLog;
use App\Models\Personnel;
use RuntimeException;

/**
 * Sanctioned cross-module surface for drafting a pending Word-engine order for one
 * employee. Other modules depend on THIS interface — never on the concrete Orders
 * implementation — so the Orders module can evolve its internals freely.
 *
 * @see \App\Modules\Orders\Infrastructure\Document\OrderDraftService
 */
interface OrderDrafter
{
    public function hasTemplate(string $code): bool;

    /**
     * @param  array<string, string>  $fieldsByLabel  placeholder label → value
     *
     * @throws RuntimeException when the template is not registered
     */
    public function draft(string $templateCode, Personnel $personnel, array $fieldsByLabel, string $orderNumber): OrderLog;
}
