<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\User;
use App\Modules\Orders\Application\Document\OrderWordTemplateRepository;
use App\Modules\Orders\Contracts\LinkedOrderCancellation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * Read-only implementation of {@see LinkedOrderCancellation}: reports the order's state and
 * builds links into the Orders screens; the cancellation itself is done there.
 */
class LinkedOrderCancellationLookup implements LinkedOrderCancellation
{
    /** Standard "Əmrin ləğvi" template (order_cancellation effect). */
    public const CANCELLATION_TEMPLATE = 'emrin_legvi';

    public function __construct(private readonly OrderWordTemplateRepository $templates) {}

    public function stateOf(int $orderId): string
    {
        $order = OrderLog::withTrashed()->find($orderId, ['id', 'status_id', 'deleted_at']);

        return match (true) {
            $order === null => self::STATE_MISSING,
            $order->trashed() => self::STATE_DELETED,
            (int) $order->status_id === OrderStatusEnum::APPROVED->value => self::STATE_APPROVED,
            (int) $order->status_id === OrderStatusEnum::CANCELLED->value => self::STATE_CANCELLED,
            default => self::STATE_PENDING,
        };
    }

    public function isVoided(string $state): bool
    {
        return in_array($state, [self::STATE_CANCELLED, self::STATE_DELETED, self::STATE_MISSING], true);
    }

    public function orderNumber(int $orderId): ?string
    {
        $number = OrderLog::withTrashed()->whereKey($orderId)->value('order_no');

        return $number !== null ? (string) $number : null;
    }

    public function orderUrl(int $orderId, User $user): ?string
    {
        $number = $this->orderNumber($orderId);

        if ($number === null || ! Route::has('orders') || ! $user->can('show-orders')) {
            return null;
        }

        return route('orders', ['search' => ['order_no' => $number]]);
    }

    public function cancellationOrderUrl(User $user): ?string
    {
        if (! Route::has('orders') || ! $user->can('add-orders') || ! $user->can('revert-orders') || $this->templates->find(self::CANCELLATION_TEMPLATE) === null) {
            return null;
        }

        return route('orders', ['create' => 1, 'preset' => self::CANCELLATION_TEMPLATE]);
    }

    public function userMayCancel(User $user, int $orderId): bool
    {
        $order = OrderLog::query()->find($orderId);

        if ($order === null) {
            return false;
        }

        $ability = (int) $order->status_id === OrderStatusEnum::APPROVED->value ? 'revert' : 'transition';

        if ((int) $order->status_id === OrderStatusEnum::CANCELLED->value) {
            return false;
        }

        return Gate::forUser($user)->allows($ability, $order);
    }
}
