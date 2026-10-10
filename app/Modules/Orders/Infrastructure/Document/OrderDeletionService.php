<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use DomainException;
use Illuminate\Support\Collection;

/**
 * Deletes orders (soft or force) — but never an approved one.
 *
 * An approved order has already changed the employee record (leave, transfer,
 * termination, hire) and has been handed to payroll/finance. Deleting it would leave
 * those effects in place with no document behind them, so it must first be cancelled,
 * which reverses the effect through OrderStatusTransitionService. The same rule backs
 * OrderLogPolicy::delete/forceDelete, so a crafted request is refused as well.
 */
class OrderDeletionService
{
    /** True while the order is approved — deleting it is not allowed. */
    public static function isProtected(OrderLog $order): bool
    {
        return (int) $order->status_id === OrderStatusEnum::APPROVED->value;
    }

    /**
     * @throws DomainException when the order is approved
     */
    public function assertDeletable(OrderLog $order): void
    {
        if (self::isProtected($order)) {
            throw new DomainException(__('orders::order_list.messages.approved_not_deletable'));
        }
    }

    /**
     * @throws DomainException when the order is approved
     */
    public function softDelete(OrderLog $order): void
    {
        $this->assertDeletable($order);

        $order->delete();
    }

    /**
     * @throws DomainException when the order is approved
     */
    public function forceDelete(OrderLog $order): void
    {
        $this->assertDeletable($order);

        $order->handleDeletion();
    }

    /**
     * Soft-deleted orders that are still approved — left behind by the time deletion was
     * not yet guarded. Their effects are still live; they need a human decision
     * (restore and cancel properly), so this only reports them.
     *
     * @return Collection<int, OrderLog>
     */
    public function deletedApproved(): Collection
    {
        return OrderLog::onlyTrashed()
            ->where('status_id', OrderStatusEnum::APPROVED->value)
            ->with('personDidDelete:id,name')
            ->orderBy('deleted_at')
            ->get();
    }
}
