<?php

namespace App\Modules\EmployeeRelations\Application\Services;

use App\Modules\EmployeeRelations\Domain\CaseStatus;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Facades\DB;

/**
 * Gecikmiş işlər üzrə araşdırıcılara xatırlatma (scheduler). Araşdırıcı təyin
 * olunmayıbsa, işi açan istifadəçiyə (anonim müraciətdə heç kimə — yalnız siyahıda
 * işarələnir). Eyni iş `cooldown_hours` ərzində təkrar xatırladılmır.
 */
class SlaReminderService
{
    public function __construct(private readonly CaseNotifier $notifier) {}

    /**
     * @return array{overdue: int, reminded_cases: int, notifications: int, skipped: bool}
     */
    public function run(?int $cooldownHours = null, ?int $maxPerRun = null): array
    {
        if (! InstalledTables::has('employee_relation_cases')) {
            return ['overdue' => 0, 'reminded_cases' => 0, 'notifications' => 0, 'skipped' => true];
        }

        $cooldownHours ??= max(1, (int) config('employee_relations.reminders.cooldown_hours', 24));
        $maxPerRun ??= max(1, (int) config('employee_relations.reminders.max_per_run', 200));

        $slaStatuses = array_values(array_map(
            fn (CaseStatus $status): string => $status->value,
            array_filter(CaseStatus::cases(), fn (CaseStatus $status): bool => $status->hasSla()),
        ));

        $overdue = RelationCase::query()
            ->whereIn('status', $slaStatuses)
            ->whereNotNull('stage_due_at')
            ->where('stage_due_at', '<', now());

        $overdueCount = (clone $overdue)->count();

        $cases = $overdue
            ->where(fn ($query) => $query
                ->whereNull('last_reminder_at')
                ->orWhere('last_reminder_at', '<=', now()->subHours($cooldownHours)))
            ->orderBy('stage_due_at')
            ->limit($maxPerRun)
            ->get();

        $investigators = DB::table('employee_relation_case_investigators')
            ->whereIn('case_id', $cases->modelKeys())
            ->get(['case_id', 'user_id'])
            ->groupBy('case_id');

        $reminded = 0;
        $notifications = 0;

        foreach ($cases as $case) {
            $recipients = collect($investigators->get($case->id, []))->pluck('user_id')->map(fn ($id): int => (int) $id)->all();

            if ($recipients === [] && $case->created_by !== null) {
                $recipients = [(int) $case->created_by];
            }

            if ($recipients === []) {
                continue;
            }

            $notifications += $this->notifier->slaReminder($recipients, $case);
            $case->forceFill([
                'last_reminder_at' => now(),
                'reminder_count' => $case->reminder_count + 1,
            ])->save();
            $reminded++;
        }

        return ['overdue' => $overdueCount, 'reminded_cases' => $reminded, 'notifications' => $notifications, 'skipped' => false];
    }
}
