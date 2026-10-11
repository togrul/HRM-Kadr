<?php

namespace App\Modules\Orders\Contracts;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * An order moved between statuses (approved, cancelled, reverted, reopened). Other modules
 * listen to this instead of reaching into Orders' models. Dispatched after commit.
 */
final class OrderStatusChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly int $orderId,
        public readonly ?string $orderNo,
        public readonly int $fromStatus,
        public readonly int $toStatus,
        /** 'applied' | 'reversed' | 'none' — what happened to the order's HR effect. */
        public readonly string $effectDirection,
    ) {}
}
