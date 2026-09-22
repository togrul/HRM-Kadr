<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceScorecardChangeRequest;
use App\Models\PerformanceScorecardItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Targets are locked once a card is active (spec §5); the only way to move one is a
 * change request: the manager or the employee proposes a new target with a reason and
 * HR approves or rejects it. Everything stays in the card's history.
 */
class TargetChangeService
{
    public function __construct(
        private readonly ScorecardService $scorecards,
        private readonly ScorecardNotifier $notifier,
    ) {}

    /**
     * @throws AuthorizationException|ValidationException
     */
    public function request(PerformanceScorecardItem $item, float $proposed, string $reason, User $user): PerformanceScorecardChangeRequest
    {
        $card = $item->scorecard;
        $this->scorecards->authorizeRole($user, $card, ['hr', 'manager', 'employee']);
        $reason = trim($reason);

        $error = match (true) {
            $card->status !== 'active' => ['change' => __('performance_evaluation::kpi.errors.scorecard_not_active')],
            $item->kpi->direction === 'range' || $item->kpi->type !== 'quantitative' => ['change' => __('performance_evaluation::kpi.change_requests.errors.not_a_target')],
            $reason === '' => ['reason' => __('performance_evaluation::kpi.errors.reason_required')],
            $item->changeRequests()->where('status', 'pending')->exists() => ['change' => __('performance_evaluation::kpi.change_requests.errors.pending_exists')],
            abs($proposed - (float) $item->target) < 0.00005 => ['change' => __('performance_evaluation::kpi.change_requests.errors.same_target')],
            default => null,
        };

        if ($error !== null) {
            throw ValidationException::withMessages($error);
        }

        $request = $item->changeRequests()->create([
            'current_target' => $item->target,
            'proposed_target' => $proposed,
            'reason' => $reason,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        $card->events()->create([
            'action' => 'target_change_requested',
            'from_status' => $card->status,
            'to_status' => $card->status,
            'reason' => __('performance_evaluation::kpi.change_requests.event', ['kpi' => $item->kpi->name, 'from' => (float) $item->target, 'to' => $proposed, 'reason' => $reason]),
            'user_id' => $user->id,
        ]);
        $this->notifier->changeRequested($request);

        return $request;
    }

    /**
     * @throws AuthorizationException|ValidationException
     */
    public function decide(PerformanceScorecardChangeRequest $request, bool $approve, ?string $note, User $user): void
    {
        $item = $request->item;
        $card = $item->scorecard;
        $this->scorecards->authorizeRole($user, $card, ['hr']);

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['change' => __('performance_evaluation::kpi.change_requests.errors.already_decided')]);
        }

        if (! $approve && trim((string) $note) === '') {
            throw ValidationException::withMessages(['reason' => __('performance_evaluation::kpi.errors.reason_required')]);
        }

        DB::transaction(function () use ($request, $approve, $note, $user, $item, $card): void {
            $request->update([
                'status' => $approve ? 'approved' : 'rejected',
                'decided_by' => $user->id,
                'decided_at' => now(),
                'decision_note' => trim((string) $note) ?: null,
            ]);

            if ($approve) {
                // A card already scaled for long leave keeps its worked share on the new target.
                $proposed = (float) $request->proposed_target;
                $item->update($item->original_target !== null && (float) $item->original_target > 0
                    ? ['original_target' => $proposed, 'target' => round($proposed * (float) $item->target / (float) $item->original_target, 4)]
                    : ['target' => $proposed]);
            }

            $card->events()->create([
                'action' => $approve ? 'target_change_approved' : 'target_change_rejected',
                'from_status' => $card->status,
                'to_status' => $card->status,
                'reason' => $item->kpi->name.($note ? ' — '.trim($note) : ''),
                'user_id' => $user->id,
            ]);
        });

        if ($approve) {
            $this->scorecards->recalculate($card);
        }

        $this->notifier->changeDecided($request->refresh());
    }
}
