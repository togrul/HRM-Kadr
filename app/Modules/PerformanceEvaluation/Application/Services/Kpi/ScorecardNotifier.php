<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceKpi;
use App\Models\PerformanceScorecard;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Contracts\ApprovalRouteResolver;
use App\Notifications\PlatformNotification;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * In-app notifications for the scorecard workflow (spec §10): the next person to act
 * hears about every step, the stage owner gets a reminder before the deadline, and the
 * owner's superior plus HR hear about it one working day after.
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
     * Tells HR once that a KPI's external source stopped answering; its cards keep the
     * last value and show it as stale.
     */
    public function connectorFailed(PerformanceKpi $kpi, string $error): int
    {
        $userIds = $this->hrUserIds();
        $payload = [
            'action' => 'performanceKpiConnector',
            'category' => __('performance_evaluation::kpi.notifications.category'),
            'message' => __('performance_evaluation::kpi.connector.notification.subject', ['kpi' => $kpi->name]),
            'name' => $kpi->name,
            'body' => __('performance_evaluation::kpi.connector.notification.body', ['kpi' => $kpi->name, 'error' => $error]),
            'kpi_id' => $kpi->id,
        ];

        $users = User::query()->whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            $user->notify(new PlatformNotification('database', $payload, $payload['message'], $payload['body']));
        }

        return $users->count();
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

        $payload = [
            'action' => 'performanceScorecard',
            'category' => __('performance_evaluation::kpi.notifications.category'),
            'message' => __('performance_evaluation::kpi.notifications.'.$key.'.subject', $replace),
            'name' => $replace['employee'],
            'body' => __('performance_evaluation::kpi.notifications.'.$key.'.body', $replace),
            'scorecard_id' => $card->id,
            'status' => $card->status,
        ];

        $users = User::query()->whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            $user->notify(new PlatformNotification('database', $payload, $payload['message'], $payload['body']));
        }

        return $users->count();
    }
}
