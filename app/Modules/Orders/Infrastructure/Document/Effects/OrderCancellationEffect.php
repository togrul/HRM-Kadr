<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Candidates\Contracts\CandidateHireReversal;
use App\Modules\Orders\Application\Document\OrderWordTemplateRepository;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderStatusTransitionService;
use DomainException;

/**
 * Revokes an earlier order of the same employee (əmrin ləğvi): approving this order
 * cancels the target through the status transition service — which reverses the
 * target's HR effect — and links the two both ways (this order's effect_state, the
 * target's `cancelled_by_order_id`) with an audit entry. Revoking this order re-approves
 * the target, re-applying its effect.
 *
 * Refused (with the reason shown): a missing or unapproved target, the order itself,
 * another employee's order, and another revocation order (no chains). A hire whose new
 * employee already has records cannot be undone; that refusal is passed on as is.
 */
class OrderCancellationEffect implements OrderEffect
{
    use RemembersEffectState;

    public const EFFECT = 'order_cancellation';

    /** The permission that takes an approved order out of the approved state. */
    public const REVERT_PERMISSION = 'revert-orders';

    public function __construct(
        private readonly OrderStatusTransitionService $transitions,
        private readonly OrderWordTemplateRepository $templates,
        private readonly CandidateHireReversal $candidates,
    ) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $this->guardActorMayRevert();
        $target = $this->target($order, $fields, $personnel);

        try {
            $this->transitions->cancel($target, __('orders::order_composer.cancellation_reason', ['number' => $order->order_no]));
        } catch (DomainException $exception) {
            throw new DomainException(__('orders::order_composer.errors.cancellation_failed', [
                'number' => $target->order_no,
                'reason' => $exception->getMessage(),
            ]), 0, $exception);
        }

        $this->link($target, $order->id);
        $this->rememberState($order, ['cancelled_order_id' => (int) $target->id]);

        activity('orders')
            ->performedOn($target)
            ->withProperties(['order_no' => $target->order_no, 'cancelled_by_order_id' => $order->id, 'cancelled_by_order_no' => $order->order_no])
            ->event('cancelled_by_order')
            ->log('order.cancelled_by_order');
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $targetId = (int) ($this->rememberedState($order)['cancelled_order_id'] ?? 0);
        $target = $targetId > 0 ? OrderLog::query()->find($targetId) : null;

        $this->guardActorMayRevert();

        // Re-approve only what this order cancelled and nobody has touched since. Approving
        // the target again runs the closed-month check of its own months.
        if ($target !== null
            && (int) $target->status_id === OrderStatusEnum::CANCELLED->value
            && (int) data_get($target->template_snapshot, 'cancelled_by_order_id') === (int) $order->id) {
            $this->link($target, null);
            $this->transitions->reopen($target);
            $this->transitions->approve($target->fresh());

            activity('orders')
                ->performedOn($target)
                ->withProperties(['order_no' => $target->order_no, 'restored_by_order_id' => $order->id])
                ->event('restored_by_order')
                ->log('order.restored_by_order');
        }

        $this->forgetState($order, ['cancelled_order_id']);
    }

    /**
     * Approving or reversing a cancellation order takes the target out of (or back into) the
     * approved state, which the transition service authorises by revert-orders — an actor who
     * may only approve orders must not reach it through this order type.
     *
     * @throws DomainException
     */
    private function guardActorMayRevert(): void
    {
        $user = auth()->user();

        if ($user !== null && ! $user->can(self::REVERT_PERMISSION)) {
            throw new DomainException(__('orders::order_composer.errors.cancellation_forbidden'));
        }
    }

    /**
     * @param  array<string,mixed>  $fields
     *
     * @throws DomainException
     */
    private function target(OrderLog $order, array $fields, Personnel $personnel): OrderLog
    {
        $id = (int) ($fields['target_order'] ?? 0);
        $target = $id > 0 ? OrderLog::query()->find($id) : null;

        if ($target === null) {
            throw new DomainException(__('orders::order_composer.errors.cancellation_target_missing'));
        }

        if ((int) $target->id === (int) $order->id) {
            throw new DomainException(__('orders::order_composer.errors.cancellation_self'));
        }

        $snapshot = (array) $target->template_snapshot;

        if ((string) $target->template_render_mode !== OrderIssueService::RENDER_MODE_DOCX
            || (int) $target->status_id !== OrderStatusEnum::APPROVED->value) {
            throw new DomainException(__('orders::order_composer.errors.cancellation_target_not_approved', ['number' => $target->order_no]));
        }

        $template = $this->templates->find((string) ($snapshot['template_code'] ?? ''));

        if ($this->subjectId($target, $snapshot, $template?->isHire() ?? false) !== (int) $personnel->id) {
            throw new DomainException(__('orders::order_composer.errors.cancellation_other_employee', ['number' => $target->order_no]));
        }

        if ($template !== null && $template->effect === self::EFFECT) {
            throw new DomainException(__('orders::order_composer.errors.cancellation_chain', ['number' => $target->order_no]));
        }

        return $target;
    }

    /**
     * The employee the target order is about: its subject, or for a hire the employee
     * that hire created.
     *
     * @param  array<string,mixed>  $snapshot
     */
    private function subjectId(OrderLog $target, array $snapshot, bool $isHire): int
    {
        if (! $isHire) {
            return (int) ($snapshot['personnel_id'] ?? 0);
        }

        $candidateId = (int) ($snapshot['candidate_id'] ?? 0);

        return $candidateId > 0 ? (int) $this->candidates->hiredPersonnelId($candidateId, (int) $target->id) : 0;
    }

    private function link(OrderLog $target, ?int $cancelledBy): void
    {
        $snapshot = (array) $target->template_snapshot;

        if ($cancelledBy === null) {
            unset($snapshot['cancelled_by_order_id']);
        } else {
            $snapshot['cancelled_by_order_id'] = $cancelledBy;
        }

        $target->forceFill(['template_snapshot' => $snapshot])->save();
    }
}
