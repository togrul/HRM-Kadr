<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Services\Orders\Document\OrderIssueService;
use Illuminate\Support\Facades\DB;

/**
 * Approves a block-engine order: flips it to approved and runs the order type's HR
 * side-effect (e.g. creating the vacation record) using the data frozen in the
 * snapshot. This replaces — for block orders — what the legacy OrderConfirmedService
 * did on approval, but routed through pluggable per-type effects.
 */
class BlockOrderApprovalService
{
    public const STATUS_APPROVED = 20;

    public function __construct(private readonly BlockOrderEffectRegistry $registry) {}

    public function approve(OrderLog $order): void
    {
        if ((string) $order->template_render_mode !== OrderIssueService::RENDER_MODE) {
            return; // not a block order — leave the legacy path alone
        }

        DB::transaction(function () use ($order) {
            $order->update(['status_id' => self::STATUS_APPROVED]);

            $snapshot = (array) $order->template_snapshot;
            $effect = $this->registry->for((string) ($snapshot['template_code'] ?? ''));
            $personnelId = $snapshot['personnel_id'] ?? null;

            if ($effect && $personnelId) {
                $personnel = Personnel::find($personnelId);
                if ($personnel) {
                    $effect->apply($order, (array) ($snapshot['fields'] ?? []), $personnel);
                }
            }
        });
    }
}
