<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceScorecard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The scheduled side of the scorecard workflow (spec §5, §5.1, §10):
 *  - deadlines: a card left unanswered in agreement is accepted automatically, stage
 *    owners are reminded two working days before their deadline and the stage is
 *    escalated one working day after it;
 *  - people: a card closes early when its owner leaves or changes position, and a new
 *    card opens for the new position so the cycle result becomes a day-weighted average.
 */
class ScorecardLifecycleService
{
    public const REMIND_WORKING_DAYS_BEFORE = 2;

    public const ESCALATE_WORKING_DAYS_AFTER = 1;

    public function __construct(
        private readonly ScorecardService $scorecards,
        private readonly ScorecardNotifier $notifier,
    ) {}

    /**
     * @return array{auto_accepted: int, reminded: int, escalated: int}
     */
    public function runDeadlines(?Carbon $today = null): array
    {
        $today = ($today ?? today())->copy()->startOfDay();
        $result = ['auto_accepted' => 0, 'reminded' => 0, 'escalated' => 0];

        $this->openCards()
            ->where('status', 'pending_agreement')
            ->whereDate('stage_due_at', '<', $today)
            ->get()
            ->each(function (PerformanceScorecard $card) use (&$result): void {
                $this->scorecards->transition($card, 'accept', null, __('performance_evaluation::kpi.auto_accept_reason'));
                $result['auto_accepted']++;
            });

        $this->openCards()
            ->whereNull('reminded_at')
            ->whereNotNull('stage_due_at')
            ->whereDate('stage_due_at', '>=', $today)
            ->whereDate('stage_due_at', '<=', $today->copy()->addWeekdays(self::REMIND_WORKING_DAYS_BEFORE))
            ->get()
            ->each(function (PerformanceScorecard $card) use (&$result): void {
                $this->notifier->remind($card);
                $card->update(['reminded_at' => now()]);
                $result['reminded']++;
            });

        $this->openCards()
            ->whereNull('escalated_at')
            ->whereNotNull('stage_due_at')
            ->whereDate('stage_due_at', '<=', $today->copy()->subWeekdays(self::ESCALATE_WORKING_DAYS_AFTER))
            ->get()
            ->each(function (PerformanceScorecard $card) use (&$result): void {
                $this->notifier->escalate($card);
                $card->update(['escalated_at' => now()]);
                $result['escalated']++;
            });

        return $result;
    }

    /**
     * @return array{terminated: int, position_changed: int, reopened: int}
     */
    public function syncPersonnel(?Carbon $today = null): array
    {
        $today = ($today ?? today())->copy()->startOfDay();
        $result = ['terminated' => 0, 'position_changed' => 0, 'reopened' => 0];

        $this->openCards()
            ->whereHas('cycle', fn ($query) => $query->where('status', '!=', 'closed'))
            ->with(['personnel', 'cycle'])
            ->get()
            ->each(function (PerformanceScorecard $card) use ($today, &$result): void {
                $personnel = $card->personnel;
                if ($personnel === null) {
                    return;
                }

                $leftOn = $personnel->getRawOriginal('leave_work_date');
                if ($leftOn !== null) {
                    $this->scorecards->closeEarly($card, 'terminated', Carbon::parse($leftOn));
                    $result['terminated']++;

                    return;
                }

                if ((int) $personnel->position_id === (int) $card->position_id) {
                    return;
                }

                $this->scorecards->closeEarly($card, 'position_changed', $today->copy()->subDay());
                $result['position_changed']++;

                if ($today->lte(Carbon::parse($card->cycle->period_end)) && $this->scorecards->openCardFor($card->cycle, $personnel, $today) !== null) {
                    $result['reopened']++;
                }
            });

        return $result;
    }

    private function openCards(): Builder
    {
        return PerformanceScorecard::query()->where('status', '!=', 'closed');
    }
}
