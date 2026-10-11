<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\User;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseEvent;
use App\Modules\EmployeeRelations\Domain\OrderCancellationState;
use App\Modules\Orders\Contracts\LinkedOrderCancellation;
use App\Modules\Orders\Contracts\OrderStatusChanged;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Apellyasiya ilə ləğv olunan (və ya tənbehsiz qərara dəyişdirilən) intizam qərarının
 * bağlı əmrinin izlənməsi (ƏM Md.190: ləğv olunmuş tənbeh şəxsi işdə qüvvədə qalmamalıdır).
 *
 * Təsdiqlənmiş əmri bu xidmət ləğv ETMİR: ləğv Orders modulunun öz axınıdır (revert-orders
 * icazəsi, yazılı səbəb, bağlanmış dövr yoxlaması). Xidmət yalnız:
 *  - işi «ləğv gözləyir» vəziyyətinə salır (`request`);
 *  - Orders əmrin statusunu dəyişəndə (OrderStatusChanged) və ya iş açılanda əmrin ləğv
 *    olunduğunu görüb vəziyyəti «tamamlandı»ya keçirir (`sync`);
 *  - işin bağlanmasına mane olur (`guardClosable`), səbəb yazılmaqla istisnanı icazə ilə
 *    təsdiq edir (`waive`).
 * Orders-a yalnız LinkedOrderCancellation kontraktı və OrderStatusChanged hadisəsi ilə
 * baxır — modelinə birbaşa asılılıq yoxdur.
 */
class OrderCancellationTracker
{
    public const WAIVE_PERMISSION = 'waive-employee-relations-order-cancellation';

    public const MIN_WAIVE_REASON = 10;

    public function __construct(
        private readonly LinkedOrderCancellation $orders,
        private readonly CaseAccess $access,
        private readonly CaseAuditLogger $audit,
        private readonly CaseNotifier $notifier,
    ) {}

    /**
     * Qərar ləğv olunub; `$orderId` əmri ləğv edilməlidir. Əmr artıq ləğv olunubsa (və ya
     * yoxdursa) gözləmə yaranmır.
     *
     * @return bool gözləmə yaradıldı
     */
    public function request(RelationCase $case, int $orderId, ?User $actor): bool
    {
        $state = $this->orders->stateOf($orderId);

        if ($this->orders->isVoided($state)) {
            $case->forceFill([
                'order_cancellation_status' => OrderCancellationState::Completed,
                'order_cancellation_order_id' => $orderId,
                'order_cancellation_requested_at' => now(),
                'order_cancellation_settled_at' => now(),
            ])->save();

            return false;
        }

        $case->forceFill([
            'order_cancellation_status' => OrderCancellationState::Pending,
            'order_cancellation_order_id' => $orderId,
            'order_cancellation_requested_at' => now(),
            'order_cancellation_settled_at' => null,
            'order_cancellation_waived_by' => null,
            'order_cancellation_waive_reason' => null,
        ])->save();

        $this->record($case, 'order_cancellation_pending', $this->orders->orderNumber($orderId), $actor);
        $this->audit->log('order_cancellation_pending', $case, ['order_log_id' => $orderId, 'order_state' => $state], $actor);
        $this->notifier->orderCancellation($case, 'order_cancellation_pending', $actor);

        return true;
    }

    /**
     * Gözləyən iş üçün əmrin cari vəziyyətinə baxır; əmr ləğv olunubsa (silinibsə)
     * vəziyyəti «tamamlandı»ya keçirir. Gözləmə yoxdursa heç nə etmir.
     */
    public function sync(RelationCase $case, ?User $actor = null): RelationCase
    {
        if (! $case->hasPendingOrderCancellation() || $case->order_cancellation_order_id === null) {
            return $case;
        }

        $orderId = (int) $case->order_cancellation_order_id;

        if (! $this->orders->isVoided($this->orders->stateOf($orderId))) {
            return $case;
        }

        DB::transaction(function () use ($case, $orderId, $actor): void {
            /** @var RelationCase|null $locked */
            $locked = RelationCase::query()->lockForUpdate()->find($case->getKey());

            // Başqa sorğu artıq tamamlayıbsa ikinci dəfə yazılmır.
            if ($locked === null || ! $locked->hasPendingOrderCancellation()) {
                $case->setRawAttributes($locked?->getAttributes() ?? $case->getAttributes(), true);

                return;
            }

            $locked->forceFill([
                'order_cancellation_status' => OrderCancellationState::Completed,
                'order_cancellation_settled_at' => now(),
            ])->save();

            $this->record($locked, 'order_cancellation_completed', $this->orders->orderNumber($orderId), $actor);
            $this->audit->log('order_cancellation_completed', $locked, ['order_log_id' => $orderId], $actor);
            $this->notifier->orderCancellation($locked, 'order_cancellation_completed', $actor);

            $case->setRawAttributes($locked->getAttributes(), true);
        });

        return $case;
    }

    /**
     * Orders əmrin statusunu dəyişəndə çağırılır: həmin əmri gözləyən işlər yenilənir.
     * Dinləyicinin xətası Orders-un əməliyyatını pozmamalıdır — vəziyyət işə baxılanda və
     * bağlananda yenə də yoxlanılır.
     */
    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        try {
            RelationCase::query()
                ->where('order_cancellation_status', OrderCancellationState::Pending->value)
                ->where('order_cancellation_order_id', $event->orderId)
                ->get()
                ->each(fn (RelationCase $case): RelationCase => $this->sync($case, auth()->user()));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * İşi bağlamazdan əvvəl: əmri ləğv gözləyən iş ya əmr ləğv olunandan, ya da istisna
     * təsdiqindən sonra bağlanır.
     *
     * @throws ValidationException
     */
    public function guardClosable(RelationCase $case): void
    {
        $this->sync($case);

        if ($case->hasPendingOrderCancellation()) {
            throw ValidationException::withMessages(['transition' => __('employee_relations::cases.messages.order_cancellation_pending')]);
        }
    }

    /** İstifadəçi ləğv gözləyən əmr üçün istisna təsdiq edə bilərmi (icazə + işə baxış səlahiyyəti). */
    public function canWaive(?User $user, RelationCase $case): bool
    {
        return $user instanceof User
            && $case->hasPendingOrderCancellation()
            && $user->can(self::WAIVE_PERMISSION)
            && $this->access->canManage($user, $case);
    }

    /**
     * Əmr qüvvədə qalır: icazəli şəxs səbəb yazmaqla işin bağlanmasına icazə verir.
     * Qərar yazılır, audit jurnalına və tarixçəyə düşür, HR-a bildirilir.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function waive(RelationCase $case, User $actor, string $reason): RelationCase
    {
        $this->sync($case, $actor);

        if (! $case->hasPendingOrderCancellation()) {
            throw ValidationException::withMessages(['waiveReason' => __('employee_relations::cases.messages.order_cancellation_not_pending')]);
        }

        if (! $this->canWaive($actor, $case)) {
            throw new AuthorizationException;
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < self::MIN_WAIVE_REASON) {
            throw ValidationException::withMessages(['waiveReason' => __('employee_relations::cases.messages.waive_reason_short', ['min' => self::MIN_WAIVE_REASON])]);
        }

        return DB::transaction(function () use ($case, $actor, $reason): RelationCase {
            $case->forceFill([
                'order_cancellation_status' => OrderCancellationState::Waived,
                'order_cancellation_settled_at' => now(),
                'order_cancellation_waived_by' => $actor->getKey(),
                'order_cancellation_waive_reason' => $reason,
            ])->save();

            $this->record($case, 'order_cancellation_waived', $reason, $actor);
            $this->audit->log('order_cancellation_waived', $case, ['order_log_id' => $case->order_cancellation_order_id], $actor);
            $this->notifier->orderCancellation($case, 'order_cancellation_waived', $actor);

            return $case;
        });
    }

    private function record(RelationCase $case, string $action, ?string $comment, ?User $actor): void
    {
        RelationCaseEvent::query()->create([
            'case_id' => $case->getKey(),
            'action' => $action,
            'comment' => filled($comment) ? $comment : null,
            'actor_user_id' => $actor?->getKey(),
            'created_at' => now(),
        ]);
    }
}
