<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardItem;
use App\Support\Database\InstalledTables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The scheduled side of the scorecard workflow (spec §5, §5.1, §10):
 *  - deadlines: a card left unanswered in agreement is accepted automatically, stage
 *    owners are reminded two working days before their deadline and the stage is
 *    escalated one working day after it;
 *  - people: a card closes early when its owner leaves or changes position, and a new
 *    card opens for the new position so the cycle result becomes a day-weighted average;
 *  - long leave: approved leave (sick, maternity, unpaid…) inside a card's period above
 *    the threshold scales the card to the days worked. Annual paid vacation is a normal
 *    entitlement and does not count.
 *
 * Deadlines count working days on the attendance calendar (holidays skipped).
 */
class ScorecardLifecycleService
{
    public const REMIND_WORKING_DAYS_BEFORE = 2;

    public const ESCALATE_WORKING_DAYS_AFTER = 1;

    public function __construct(
        private readonly ScorecardService $scorecards,
        private readonly ScorecardNotifier $notifier,
        private readonly WorkingDays $workingDays,
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
            ->whereDate('stage_due_at', '<=', $this->workingDays->add($today, self::REMIND_WORKING_DAYS_BEFORE))
            ->get()
            ->each(function (PerformanceScorecard $card) use (&$result): void {
                $this->notifier->remind($card);
                $card->update(['reminded_at' => now()]);
                $result['reminded']++;
            });

        $this->openCards()
            ->whereNull('escalated_at')
            ->whereNotNull('stage_due_at')
            ->whereDate('stage_due_at', '<=', $this->workingDays->sub($today, self::ESCALATE_WORKING_DAYS_AFTER))
            ->get()
            ->each(function (PerformanceScorecard $card) use (&$result): void {
                $this->notifier->escalate($card);
                $card->update(['escalated_at' => now()]);
                $result['escalated']++;
            });

        return $result;
    }

    /**
     * @return array{terminated: int, position_changed: int, reopened: int, manager_changed: int}
     */
    public function syncPersonnel(?Carbon $today = null): array
    {
        $today = ($today ?? today())->copy()->startOfDay();
        $result = ['terminated' => 0, 'position_changed' => 0, 'reopened' => 0, 'manager_changed' => 0];

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

                if ($card->is_additional || (int) $personnel->position_id === (int) $card->position_id) {
                    if (! in_array($card->status, ['approved', 'closed'], true) && $this->scorecards->reassignManager($card)) {
                        $result['manager_changed']++;
                    }

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

    /**
     * Returns the number of cards whose leave adjustment changed.
     */
    public function syncLongLeave(): int
    {
        if (! InstalledTables::has('leaves')) {
            return 0;
        }

        $cards = $this->openCards()
            ->whereIn('status', ['draft', 'pending_agreement', 'active', 'self_review', 'manager_review'])
            ->with(['personnel:id,tabel_no', 'cycle'])
            ->get();

        $leaves = Leave::query()
            ->whereIn('tabel_no', $cards->pluck('personnel.tabel_no')->filter()->unique())
            ->where('status_id', OrderStatusEnum::APPROVED->value)
            ->get(['tabel_no', 'starts_at', 'ends_at', 'total_days'])
            ->groupBy('tabel_no');

        return $cards->filter(function (PerformanceScorecard $card) use ($leaves): bool {
            $from = Carbon::parse($card->valid_from);
            $to = Carbon::parse($card->valid_to);
            $days = $leaves->get((string) $card->personnel?->tabel_no, collect())->sum(function (Leave $leave) use ($from, $to): int {
                $start = max($from, Carbon::parse($leave->starts_at)->startOfDay());
                $end = min($to, $leave->ends_at ? Carbon::parse($leave->ends_at)->startOfDay() : Carbon::parse($leave->starts_at)->addDays(max(1, (int) $leave->total_days) - 1));

                return $start->lte($end) ? (int) $start->diffInDays($end) + 1 : 0;
            });

            return $this->scorecards->applyLeave($card, $days);
        })->count();
    }

    /** Days before a card's end when missing actuals are chased (spec §10). */
    public const ACTUALS_REMINDER_DAYS = 3;

    /** Check-ins are expected monthly. */
    public const CHECKIN_EVERY_DAYS = 30;

    /**
     * Chases actuals still missing three days before a card ends, and a check-in when
     * the last one (or the card's start) is a month old.
     *
     * @return array{actuals_missing: int, checkin_due: int}
     */
    public function remindActualsAndCheckins(?Carbon $today = null): array
    {
        $today = ($today ?? today())->copy()->startOfDay();
        $result = ['actuals_missing' => 0, 'checkin_due' => 0];

        $this->openCards()
            ->where('status', 'active')
            ->with(['items.kpi:id,data_source,source_metric', 'items.actuals:id,performance_scorecard_item_id,approved_at', 'checkins:id,performance_scorecard_id,checkin_date'])
            ->get()
            ->each(function (PerformanceScorecard $card) use ($today, &$result): void {
                $end = Carbon::parse($card->valid_to)->startOfDay();

                if ($card->actuals_reminded_at === null && $today->gte($end->copy()->subDays(self::ACTUALS_REMINDER_DAYS)) && $today->lte($end)) {
                    $missing = $card->items->filter(fn ($item): bool => $item->kpi->data_source === 'manual' && $item->actuals->whereNotNull('approved_at')->isEmpty())->count();
                    if ($missing > 0) {
                        $this->notifier->actualsMissing($card, $missing);
                        $result['actuals_missing']++;
                    }
                    $card->update(['actuals_reminded_at' => now()]);
                }

                $lastTouch = collect([
                    Carbon::parse($card->valid_from),
                    $card->checkins->max('checkin_date') ? Carbon::parse($card->checkins->max('checkin_date')) : null,
                    $card->checkin_reminded_at,
                ])->filter()->max();

                if ($lastTouch->copy()->addDays(self::CHECKIN_EVERY_DAYS)->lte($today) && $today->lt($end)) {
                    $this->notifier->checkinDue($card);
                    $card->update(['checkin_reminded_at' => now()]);
                    $result['checkin_due']++;
                }
            });

        return $result;
    }

    /**
     * A KPI whose forecast falls under its threshold warns the employee and the manager
     * once; the mark clears when the forecast recovers, so a later fall warns again.
     */
    public function notifyRedZone(): int
    {
        $sent = 0;

        PerformanceScorecardItem::query()
            ->whereHas('scorecard', fn ($query) => $query->where('status', 'active'))
            ->whereNotNull('forecast_achievement')
            ->whereNotNull('threshold')
            ->with(['scorecard', 'kpi:id,name'])
            ->get()
            ->each(function (PerformanceScorecardItem $item) use (&$sent): void {
                $red = (float) $item->forecast_achievement < (float) $item->threshold;

                if ($red && $item->red_notified_at === null) {
                    $this->notifier->redZone($item);
                    $item->update(['red_notified_at' => now()]);
                    $sent++;
                } elseif (! $red && $item->red_notified_at !== null) {
                    $item->update(['red_notified_at' => null]);
                }
            });

        return $sent;
    }

    /**
     * @return Builder<PerformanceScorecard>
     */
    private function openCards(): Builder
    {
        return PerformanceScorecard::query()->where('status', '!=', 'closed');
    }
}
