<?php

namespace App\Modules\Orders\Policies;

use App\Models\OrderLog;
use App\Models\User;
use App\Modules\Orders\Application\Services\OrderVisibilityService;
use App\Modules\Orders\Infrastructure\Document\OrderDeletionService;

/**
 * Hər qeyd üzrə qərar həm icazəni, həm də struktur görünürlüyünü tələb edir: əmrin
 * işçiləri (və ya işə qəbul hədəf strukturu) istifadəçinin strukturlarında olmalıdır.
 */
class OrderLogPolicy
{
    public function __construct(private readonly OrderVisibilityService $visibility) {}

    public function viewAny(User $user): bool
    {
        return $user->can('show-orders');
    }

    public function view(User $user, OrderLog $orderLog): bool
    {
        return $user->can('show-orders') && $this->visibility->canSee($user, $orderLog);
    }

    public function update(User $user, OrderLog $orderLog): bool
    {
        return $user->can('edit-orders') && $this->visibility->canSee($user, $orderLog);
    }

    /** An approved order is never deletable — it is cancelled first (which reverses its effect). */
    public function delete(User $user, OrderLog $orderLog): bool
    {
        return $user->can('delete-orders')
            && ! OrderDeletionService::isProtected($orderLog)
            && $this->visibility->canSee($user, $orderLog);
    }

    public function restore(User $user, OrderLog $orderLog): bool
    {
        return $user->can('delete-orders') && $this->visibility->canSee($user, $orderLog);
    }

    /** Same rule as delete: an approved order (even one already in the trash) cannot be purged. */
    public function forceDelete(User $user, OrderLog $orderLog): bool
    {
        return $user->can('delete-orders')
            && ! OrderDeletionService::isProtected($orderLog)
            && $this->visibility->canSee($user, $orderLog);
    }

    /**
     * Taking an order out of the approved state (revert to pending or cancel) undoes its
     * HR effect after the fact, so it needs its own permission rather than add-orders.
     */
    public function revert(User $user, OrderLog $orderLog): bool
    {
        return $user->can('revert-orders') && $this->visibility->canSee($user, $orderLog);
    }

    /** Status transitions (approve/cancel/reopen) and duplicating — add-orders, within scope. */
    public function transition(User $user, OrderLog $orderLog): bool
    {
        return $user->can('add-orders') && $this->visibility->canSee($user, $orderLog);
    }

    /** Word/PDF download of the order document — export-orders, within scope. */
    public function download(User $user, OrderLog $orderLog): bool
    {
        return $user->can('export-orders') && $this->visibility->canSee($user, $orderLog);
    }
}
