<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Models\User;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Notifications\PlatformNotification;
use App\Services\UserPersonnelLinkResolver;

/**
 * Mövcud platforma bildirişi (database kanalı) ilə xəbərdarlıqlar. Mətnlər şəxsi
 * məlumat daşımır: yalnız iş nömrəsi və mərhələ — bildiriş siyahısı başqa ekranlarda da
 * görünə bilər.
 */
class CaseNotifier
{
    public function __construct(
        private readonly UserPersonnelLinkResolver $links,
        private readonly CaseAccess $access,
    ) {}

    /** Qərar barəsində şikayət olunan işçiyə və müraciət edənə bildirilir. */
    public function decisionAnnounced(RelationCase $case): void
    {
        $recipients = [];

        if ($case->subject_personnel_id !== null) {
            $recipients[] = $this->links->userIdsByPersonnel([(int) $case->subject_personnel_id])[(int) $case->subject_personnel_id] ?? null;
        }

        $recipients[] = $case->reporter_user_id;

        $this->send(array_filter($recipients), $case, 'decision');
    }

    /** Apellyasiyanın nəticəsi apellyasiya verənə bildirilir. */
    public function appealResolved(RelationCase $case, ?int $appellantUserId): void
    {
        $this->send(array_filter([$appellantUserId]), $case, 'appeal_resolved');
    }

    /**
     * Ləğv gözləyən/tamamlanan/istisna təsdiqlənən bağlı əmr barədə işi idarə edən HR
     * əməkdaşlarına (hərəkəti edən istisna olmaqla) bildiriş. `$kind`:
     * order_cancellation_pending | order_cancellation_completed | order_cancellation_waived.
     */
    public function orderCancellation(RelationCase $case, string $kind, ?User $actor = null): int
    {
        $recipients = User::query()
            ->permission(CaseAccess::MANAGE)
            ->when($actor !== null, fn ($query) => $query->whereKeyNot($actor?->getKey()))
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->filter(fn (User $user): bool => $this->access->canManage($user, $case))
            ->map(fn (User $user): int => (int) $user->getKey())
            ->all();

        return $this->send($recipients, $case, $kind);
    }

    /**
     * @param  list<int>  $userIds
     */
    public function slaReminder(array $userIds, RelationCase $case): int
    {
        return $this->send($userIds, $case, 'sla_overdue', [
            'status' => $case->status->label(),
            'due_at' => $case->stage_due_at?->format('d.m.Y') ?? '—',
        ]);
    }

    /**
     * @param  array<int|string, int|string|null>  $userIds
     * @param  array<string, string>  $extra
     */
    private function send(array $userIds, RelationCase $case, string $kind, array $extra = []): int
    {
        $users = User::query()->whereIn('id', array_values(array_unique(array_map('intval', array_filter($userIds)))))->get();
        $params = ['case' => $case->label(), ...$extra];

        foreach ($users as $user) {
            $payload = [
                'action' => 'employeeRelationsCase',
                'kind' => $kind,
                'category' => __('employee_relations::cases.title'),
                'message' => __('employee_relations::cases.notifications.'.$kind.'.subject', $params),
                'name' => $case->label(),
                'body' => __('employee_relations::cases.notifications.'.$kind.'.body', $params),
                'case_id' => (int) $case->getKey(),
            ];

            $user->notify(new PlatformNotification('database', $payload, $payload['message'], $payload['body']));
        }

        return $users->count();
    }
}
