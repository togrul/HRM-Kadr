<?php

namespace App\Modules\Orders\Contracts;

use App\Models\User;

/**
 * Sanctioned cross-module surface for modules that hold a link to an order they may need
 * to see annulled (e.g. Employee Relations, after an appeal annuls a disciplinary
 * decision). It only READS the order's state and points the user to Orders' own screens:
 * cancelling stays with the Orders approval flow (revert-orders permission, written
 * reason, closed-period guard) — a caller can never cancel an order through this.
 *
 * Changes are announced to listeners by {@see OrderStatusChanged}.
 *
 * @see \App\Modules\Orders\Infrastructure\Document\LinkedOrderCancellationLookup
 */
interface LinkedOrderCancellation
{
    /** Still has to be approved: its effect has not been applied (or was reverted). */
    public const STATE_PENDING = 'pending';

    /** Approved: its effect (e.g. the sanction record) stands. */
    public const STATE_APPROVED = 'approved';

    public const STATE_CANCELLED = 'cancelled';

    /** Moved to the trash (only non-approved orders can be deleted). */
    public const STATE_DELETED = 'deleted';

    /** No such order. */
    public const STATE_MISSING = 'missing';

    /** One of the STATE_* values. */
    public function stateOf(int $orderId): string;

    /** True when the order no longer stands: cancelled or deleted. */
    public function isVoided(string $state): bool;

    public function orderNumber(int $orderId): ?string;

    /** Orders list narrowed to this order, where it is cancelled through the usual flow; null when the order is missing or the user may not open Orders. */
    public function orderUrl(int $orderId, User $user): ?string;

    /** Orders composer with the "Əmrin ləğvi" order type preselected; null when that template is not registered or the user may not issue orders. */
    public function cancellationOrderUrl(User $user): ?string;

    /** The user may take THIS order out of its current state (approved → revert-orders, pending → add-orders), in their scope. */
    public function userMayCancel(User $user, int $orderId): bool;
}
