<?php

namespace App\Modules\LearningLibrary\Application\Services;

use App\Models\EmployeeContentAssignment;
use App\Models\EmployeeContentAsset;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Application\Services\MyHr\LearningAssignmentManagerService;
use App\Notifications\PlatformNotification;

class LearningLibraryAutomationService
{
    public function run(): array
    {
        return [
            'auto_assigned' => $this->autoAssignNewHires(),
            'overdue_marked' => $this->markOverdueAssignments(),
            'reminders_sent' => $this->sendReminders(),
        ];
    }

    public function autoAssignNewHires(): int
    {
        $lookbackDays = max(1, (int) config('personnel.my_hr.learning.automation.new_hire_lookback_days', 30));
        $dueDays = max(1, (int) config('personnel.my_hr.learning.automation.default_due_days', 14));
        $personnelIds = Personnel::query()
            ->active()
            ->whereDate('join_work_date', '>=', now()->subDays($lookbackDays)->toDateString())
            ->pluck('id');

        if ($personnelIds->isEmpty()) {
            return 0;
        }

        $service = app(LearningAssignmentManagerService::class);
        $count = 0;
        $dueAt = now()->addDays($dueDays)->toDateString();

        EmployeeContentAsset::query()
            ->where('is_active', true)
            ->where('auto_assign_new_hires', true)
            ->orderBy('id')
            ->get()
            ->each(function (EmployeeContentAsset $asset) use ($personnelIds, $service, $dueAt, &$count): void {
                $count += $service->assignMany($personnelIds->all(), (int) $asset->id, $dueAt, null);
            });

        return $count;
    }

    public function markOverdueAssignments(): int
    {
        return EmployeeContentAssignment::query()
            ->whereIn('status', ['assigned', 'opened'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereDoesntHave('view', fn ($query) => $query->whereNotNull('completed_at'))
            ->update(['status' => 'overdue']);
    }

    public function sendReminders(): int
    {
        $daysAhead = max(1, (int) config('personnel.my_hr.learning.automation.reminder_days_ahead', 3));
        $cooldownHours = max(1, (int) config('personnel.my_hr.learning.automation.reminder_cooldown_hours', 24));
        $maxPerRun = max(1, (int) config('personnel.my_hr.learning.automation.max_reminders_per_run', 150));
        $cooldownCutoff = now()->subHours($cooldownHours);

        $assignments = EmployeeContentAssignment::query()
            ->with(['asset:id,title', 'personnel.userLinks.user'])
            ->whereIn('status', ['assigned', 'opened', 'overdue'])
            ->where(function ($query) use ($cooldownCutoff): void {
                $query->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<=', $cooldownCutoff);
            })
            ->where(function ($query) use ($daysAhead): void {
                $query->whereNotNull('due_at')->where('due_at', '<=', now()->addDays($daysAhead))
                    ->orWhere('status', 'overdue');
            })
            ->orderByRaw("CASE WHEN status = 'overdue' THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->limit($maxPerRun)
            ->get();

        $sent = 0;

        foreach ($assignments as $assignment) {
            /** @var User|null $user */
            $user = $assignment->personnel?->userLinks?->first()?->user;
            if (! $user) {
                continue;
            }

            $user->notify(new PlatformNotification(
                'database',
                [
                    'action' => 'myHrLearningReminder',
                    'category' => __('personnel::my_hr.learning.title'),
                    'message' => __('personnel::my_hr.learning.messages.reminder_message'),
                    'name' => $assignment->asset?->title ?: __('personnel::my_hr.learning.title'),
                    'body' => __('personnel::my_hr.learning.messages.reminder_body', [
                        'title' => $assignment->asset?->title ?: __('personnel::my_hr.learning.title'),
                        'due_at' => optional($assignment->due_at)?->format('d.m.Y') ?: '—',
                    ]),
                ],
                __('personnel::my_hr.learning.messages.reminder_subject'),
                __('personnel::my_hr.learning.messages.reminder_body', [
                    'title' => $assignment->asset?->title ?: __('personnel::my_hr.learning.title'),
                    'due_at' => optional($assignment->due_at)?->format('d.m.Y') ?: '—',
                ])
            ));

            $assignment->forceFill(['last_reminder_at' => now()])->save();
            $sent++;
        }

        return $sent;
    }
}
