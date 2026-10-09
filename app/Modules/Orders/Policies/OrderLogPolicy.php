<?php

namespace App\Modules\Orders\Policies;

use App\Models\OrderLog;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\OrderDeletionService;

class OrderLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-orders');
    }

    public function view(User $user, OrderLog $orderLog): bool
    {
        return $user->can('show-orders');
    }

    public function update(User $user, OrderLog $orderLog): bool
    {
        return $user->can('edit-orders');
    }

    /** An approved order is never deletable — it is cancelled first (which reverses its effect). */
    public function delete(User $user, OrderLog $orderLog): bool
    {
        return $user->can('delete-orders') && ! OrderDeletionService::isProtected($orderLog);
    }

    public function restore(User $user, OrderLog $orderLog): bool
    {
        return $user->can('delete-orders');
    }

    /** Same rule as delete: an approved order (even one already in the trash) cannot be purged. */
    public function forceDelete(User $user, OrderLog $orderLog): bool
    {
        return $user->can('delete-orders') && ! OrderDeletionService::isProtected($orderLog);
    }

    /**
     * Taking an order out of the approved state (revert to pending or cancel) undoes its
     * HR effect after the fact, so it needs its own permission rather than add-orders.
     */
    public function revert(User $user, OrderLog $orderLog): bool
    {
        return $user->can('revert-orders');
    }
}
