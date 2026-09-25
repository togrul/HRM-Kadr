<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceCycle;
use App\Models\PerformanceKpi;
use App\Models\PerformanceScorecard;
use App\Models\PerformanceScorecardChangeRequest;
use App\Models\PerformanceScorecardItem;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Contracts\ApprovalRouteResolver;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Who hears about what in the KPI workflow (spec §10): the next person to act hears
 * about every step, the stage owner gets a reminder before the deadline and the owner's
 * superior plus HR one working day after; plus cycle opening, missing actuals, check-ins,
 * red-zone forecasts, target changes, manager changes, connector outages and the bonus
 * fund. KpiNotificationDelivery decides the channels and the wording.
 */
class ScorecardNotifier
{
    /** action → [who hears about it, message key] */
    private const ON_TRANSITION = [
        'send_for_agreement' => ['employee', 'agreement_requested'],
        'accept' => ['manager', 'accepted'],
        'reject' => ['manager', 'rejected'],
        'start_self_review' => ['employee', 'self_review_started'],
        'submit_self_review' => ['manager', 'self_review_submitted'],
        'submit_manager_review' => ['hr', 'calibration_ready'],
        'approve' => ['employee', 'approved'],
        'return' => ['manager', 'returned'],
    ];

    public function __construct(
        private readonly ScorecardService $scorecards,
        private readonly ApprovalRouteResolver $routes,
        private readonly KpiNotificationDelivery $delivery,
    ) {}

    public function transitioned(PerformanceScorecard $card, string $action, ?string $reason = null): void
    {
        [$role, $key] = self::ON_TRANSITION[$action] ?? [null, null];
        if ($role === null) {
            return;
        }

        $this->send($this->recipients($card, $role), $card, $key, ['reason' => (string) $reason]);
    }

    public function remind(PerformanceScorecard $card): int
    {
        $role = PerformanceScorecard::STAGE_OWNER[$card->status] ?? null;

        return $role ? $this->send($this->recipients($card, $role), $card, 'reminder') : 0;
    }

    /**
     * The overdue stage goes up a level: to the owner's superior (the manager when the
     * employee is late, the manager's own manager otherwise) and to HR.
     */
    public function escalate(PerformanceScorecard $card): int
    {
        $owner = PerformanceScorecard::STAGE_OWNER[$card->status] ?? null;

        $superiorIds = match ($owner) {
            'employee' => $this->recipients($card, 'manager'),
            'manager' => $this->managersManager($card),
            default => [],
        };

        return $this->send(array_values(array_unique([...$superiorIds, ...$this->recipients($card, 'hr')])), $card, 'escalation');
    }

    /**
     * @return array<int, int>
     */
    private function recipients(PerformanceScorecard $card, string $role): array
    {
        return match ($role) {
            'employee' => $this->scorecards->userIdsForPersonnel($card->personnel_id),
            'manager' => $this->scorecards->userIdsForPersonnel($card->manager_personnel_id),
            'hr' => $this->hrUserIds(),
            default => [],
        };
    }

    /**
     * @return array<int, int>
     */
    private function managersManager(PerformanceScorecard $card): array
    {
        $manager = $card->manager_personnel_id ? Personnel::query()->find($card->manager_personnel_id) : null;
        $superiorId = $manager ? ($this->routes->manager($manager)['id'] ?? null) : null;

        return $this->scorecards->userIdsForPersonnel($superiorId);
    }

    /**
     * The new manager takes the card over; the previous one hears that they may still
     * leave a comment (spec §5.1).
     */
    public function managerChanged(PerformanceScorecard $card, ?int $previousManagerPersonnelId): int
    {
        return $this->send($this->recipients($card, 'manager'), $card, 'manager_assigned')
            + $this->send($this->scorecards->userIdsForPersonnel($previousManagerPersonnelId), $card, 'manager_released');
    }

    /**
     * "The cycle is open, fill in the cards" — one message per manager (spec §10).
     *
     * @param  Collection<int, PerformanceScorecard>  $cards
     */
    public function cycleOpened(PerformanceCycle $cycle, Collection $cards): int
    {
        $sent = 0;
        foreach ($cards->groupBy('manager_personnel_id') as $managerPersonnelId => $managed) {
            $sent += $this->send($this->scorecards->userIdsForPersonnel((int) $managerPersonnelId ?: null), $managed->first(), 'cycle_opened', [
                'count' => (string) $managed->count(),
            ]);
        }

        return $sent;
    }

    /**
     * Actuals still missing close to the end of the period: the employee and the manager.
     */
    public function actualsMissing(PerformanceScorecard $card, int $missing): int
    {
        return $this->send([...$this->recipients($card, 'employee'), ...$this->recipients($card, 'manager')], $card, 'actuals_missing', ['count' => (string) $missing]);
    }

    public function checkinDue(PerformanceScorecard $card): int
    {
        return $this->send([...$this->recipients($card, 'employee'), ...$this->recipients($card, 'manager')], $card, 'checkin_due');
    }

    public function fundExceeded(PerformanceCycle $cycle, float $total, float $fund, string $currency): int
    {
        return $this->delivery->deliver($this->hrUserIds(), 'fund_exceeded', [
            'cycle' => $cycle->name,
            'total' => number_format($total, 2, '.', ' '),
            'fund' => number_format($fund, 2, '.', ' '),
            'currency' => $currency,
        ], ['cycle_id' => $cycle->id]);
    }

    public function redZone(PerformanceScorecardItem $item): int
    {
        $card = $item->scorecard;
        $extra = [
            'kpi' => (string) $item->kpi?->name,
            'forecast' => (string) round((float) $item->forecast_achievement, 1),
            'threshold' => (string) (float) $item->threshold,
        ];

        return $this->send($this->recipients($card, 'employee'), $card, 'red_zone', $extra)
            + $this->send($this->recipients($card, 'manager'), $card, 'red_zone', $extra);
    }

    public function changeRequested(PerformanceScorecardChangeRequest $request): int
    {
        return $this->send($this->hrUserIds(), $request->item->scorecard, 'change_requested', [
            'kpi' => (string) $request->item->kpi?->name,
            'from' => (string) (float) $request->current_target,
            'to' => (string) (float) $request->proposed_target,
            'reason' => $request->reason,
        ]);
    }

    public function changeDecided(PerformanceScorecardChangeRequest $request): int
    {
        return $this->send(array_filter([(int) $request->requested_by]), $request->item->scorecard, 'change_'.$request->status, [
            'kpi' => (string) $request->item->kpi?->name,
            'to' => (string) (float) $request->proposed_target,
            'reason' => (string) $request->decision_note,
        ]);
    }

    /**
     * Tells HR once that a KPI's external source stopped answering; its cards keep the
     * last value and show it as stale.
     */
    public function connectorFailed(PerformanceKpi $kpi, string $error): int
    {
        return $this->delivery->deliver($this->hrUserIds(), 'connector_failed', ['kpi' => $kpi->name, 'error' => $error], ['kpi_id' => $kpi->id]);
    }

    /**
     * @return array<int, int>
     */
    private function hrUserIds(): array
    {
        try {
            return User::permission('manage-performance-evaluation')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        } catch (PermissionDoesNotExist) {
            return [];
        }
    }

    /**
     * @param  array<int, int>  $userIds
     * @param  array<string, string>  $extra
     */
    private function send(array $userIds, PerformanceScorecard $card, string $key, array $extra = []): int
    {
        if ($userIds === []) {
            return 0;
        }

        $card->loadMissing(['personnel:id,surname,name,patronymic', 'cycle:id,name']);
        $replace = [
            'employee' => (string) $card->personnel?->fullname,
            'cycle' => (string) $card->cycle?->name,
            'due' => $card->stage_due_at?->format('d.m.Y') ?? '—',
            'status' => __('performance_evaluation::kpi.card_statuses.'.$card->status),
            ...$extra,
        ];

        return $this->delivery->deliver($userIds, $key, $replace, ['scorecard_id' => $card->id, 'status' => $card->status]);
    }
}
