<?php

namespace App\Services\Orders\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;

/**
 * Backwards-compatible facade for approving a Word-engine order. The full status
 * workflow (approve / cancel / reopen / revert, with effect apply & reverse) now lives
 * in OrderStatusTransitionService; this thin wrapper keeps the original approve() entry
 * point that callers and tests use.
 */
class OrderApprovalService
{
    public const STATUS_APPROVED = OrderStatusEnum::APPROVED->value;

    public function __construct(private readonly OrderStatusTransitionService $transitions) {}

    public function approve(OrderLog $order): void
    {
        $this->transitions->approve($order);
    }
}
