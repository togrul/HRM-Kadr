<?php

namespace App\Services\Orders\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Services\ImportCandidateToPersonnel;
use App\Services\Orders\Document\Effects\OrderEffectCatalog;
use App\Support\Language\AzerbaijaniDateFormatter;
use DomainException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The single, guarded entry point for changing a Word-engine order's status.
 *
 * Rather than flipping status_id directly, every move goes through the transition
 * graph below, which (a) refuses illegal jumps and (b) runs the right HR side-effect
 * in the right direction: approving a pending order applies its effect (leave record,
 * transfer, termination, rename, hire); cancelling or reverting an approved order
 * reverses it so the employee record returns to its pre-order state. All of it runs in
 * one transaction, and OrderLog's activity log captures the status change.
 *
 *   pending(10)  → approved(20)  | cancelled(30)
 *   approved(20) → cancelled(30) | pending(10, revert)
 *   cancelled(30)→ pending(10, reopen)
 */
class OrderStatusTransitionService
{
    /** Allowed target statuses per current status. */
    private const GRAPH = [
        OrderStatusEnum::PENDING->value => [OrderStatusEnum::APPROVED->value, OrderStatusEnum::CANCELLED->value],
        OrderStatusEnum::APPROVED->value => [OrderStatusEnum::CANCELLED->value, OrderStatusEnum::PENDING->value],
        OrderStatusEnum::CANCELLED->value => [OrderStatusEnum::PENDING->value],
    ];

    /** AppealStatus id a candidate moves to once a hire order is approved ("Qəbul olundu"). */
    private const CANDIDATE_HIRED_STATUS = 70;

    public function __construct(
        private readonly OrderWordTemplateRepository $templates,
        private readonly OrderEffectCatalog $effects,
        private readonly ImportCandidateToPersonnel $candidateImport,
        private readonly AzerbaijaniDateFormatter $dates,
    ) {}

    /** Approve a pending order (applies its HR side-effect). */
    public function approve(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::APPROVED);
    }

    /** Cancel an order. Reverses the side-effect if it had been approved. */
    public function cancel(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::CANCELLED);
    }

    /** Re-open a cancelled order back to pending. */
    public function reopen(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::PENDING);
    }

    /** Revoke an approved order back to pending (reverses the side-effect). */
    public function revert(OrderLog $order): void
    {
        $this->transition($order, OrderStatusEnum::PENDING);
    }

    /**
     * The statuses this order may move to right now (for building the UI actions).
     *
     * @return array<int,int>
     */
    public function allowedTargets(OrderLog $order): array
    {
        return self::GRAPH[(int) $order->status_id] ?? [];
    }

    public function transition(OrderLog $order, OrderStatusEnum $to): void
    {
        if ((string) $order->template_render_mode !== OrderIssueService::RENDER_MODE_DOCX) {
            throw new RuntimeException('Only Word-engine orders support status transitions here.');
        }

        $from = (int) $order->status_id;
        $target = $to->value;

        if ($from === $target) {
            return; // no-op
        }

        if (! in_array($target, self::GRAPH[$from] ?? [], true)) {
            throw new DomainException(__('orders::order_composer.errors.invalid_transition'));
        }

        DB::transaction(function () use ($order, $from, $target) {
            // Approving applies the effect; leaving an approved state reverses it.
            // pending↔cancelled carry no side-effect.
            if ($target === OrderStatusEnum::APPROVED->value) {
                $this->applyEffect($order);
            } elseif ($from === OrderStatusEnum::APPROVED->value) {
                $this->reverseEffect($order);
            }

            $order->update(['status_id' => $target]);
        });
    }

    private function applyEffect(OrderLog $order): void
    {
        $snapshot = (array) $order->template_snapshot;
        $template = $this->templates->find((string) ($snapshot['template_code'] ?? ''));
        if (! $template) {
            return;
        }

        // Hire converts the selected candidate into an active employee.
        if ($template->isHire()) {
            $this->hire($template, $snapshot);

            return;
        }

        $effect = $this->effects->for($template->effect);
        $personnel = $this->personnel($snapshot);
        if ($effect && $personnel) {
            $effect->apply($order, $this->effectFields($template, (array) ($snapshot['fields'] ?? [])), $personnel);
        }
    }

    private function reverseEffect(OrderLog $order): void
    {
        $snapshot = (array) $order->template_snapshot;
        $template = $this->templates->find((string) ($snapshot['template_code'] ?? ''));
        if (! $template) {
            return;
        }

        // Converting a candidate into an employee cannot be safely undone here.
        if ($template->isHire()) {
            throw new DomainException(__('orders::order_composer.errors.hire_irreversible'));
        }

        $effect = $this->effects->for($template->effect);
        $personnel = $this->personnel($snapshot);
        if ($effect && $personnel) {
            $effect->reverse($order, $this->effectFields($template, (array) ($snapshot['fields'] ?? [])), $personnel);
        }
    }

    /**
     * @param  array<string,mixed>  $snapshot
     */
    private function personnel(array $snapshot): ?Personnel
    {
        $id = $snapshot['personnel_id'] ?? null;

        return $id ? Personnel::find($id) : null;
    }

    /**
     * @param  array<string,mixed>  $snapshot
     */
    private function hire(OrderWordTemplate $template, array $snapshot): void
    {
        $candidateId = $snapshot['candidate_id'] ?? null;
        $positionId = $snapshot['hire_position_id'] ?? null;
        if (! $candidateId || ! $positionId) {
            return;
        }

        $joinDate = $this->dates->parse(($this->effectFields($template, (array) ($snapshot['fields'] ?? [])))['start_date'] ?? null);
        $structureId = $snapshot['hire_structure_id'] ?? null;

        $this->candidateImport->handle([[
            'personnel_id' => (int) $candidateId,
            'structure_id' => $structureId,
            'position_id' => (int) $positionId,
            'join_date' => $joinDate?->toDateString() ?? today()->toDateString(),
        ]], OrderStatusEnum::APPROVED->value);

        // The candidate is now hired: move them off the "Əmrə hazır" (30) list to
        // "Qəbul olundu" (70) so they no longer surface in the hire picker.
        \App\Models\Candidate::query()->whereKey($candidateId)->update([
            'status_id' => self::CANDIDATE_HIRED_STATUS,
        ]);

        // Consume the staff-schedule slot the hire fills (filled +1, vacant recomputed).
        app(\App\Services\Staff\StaffScheduleVacancyService::class)
            ->consumeForHire($structureId ? (int) $structureId : null, (int) $positionId);
    }

    /**
     * Translate the order's raw field values (keyed by variable token) into the effect's
     * structured inputs (keyed by role) using the template's variable→role mapping.
     *
     * @param  array<string,mixed>  $rawFields  token => value
     * @return array<string,mixed>  role => value
     */
    private function effectFields(OrderWordTemplate $template, array $rawFields): array
    {
        $fields = [];
        foreach ($template->variables ?? [] as $variable) {
            $role = $variable['effect_role'] ?? null;
            $token = $variable['token'] ?? null;
            if ($role && $token && array_key_exists($token, $rawFields)) {
                $fields[$role] = $rawFields[$token];
            }
        }

        return $fields;
    }
}
