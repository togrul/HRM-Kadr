<?php

namespace App\Modules\EmployeeLifecycle\Application\Services;

use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LifecycleReminderService
{
    public function run(?int $daysAhead = null, ?int $cooldownHours = null, ?int $maxPerRun = null): array
    {
        if (! Schema::hasTable('employee_lifecycle_events') || ! Schema::hasTable('employee_lifecycle_tasks')) {
            return [
                'event_reminders_sent' => 0,
                'task_reminders_sent' => 0,
                'skipped' => true,
            ];
        }

        $daysAhead ??= max(0, (int) config('employee_lifecycle.reminders.days_ahead', 3));
        $cooldownHours ??= max(1, (int) config('employee_lifecycle.reminders.cooldown_hours', 24));
        $maxPerRun ??= max(1, (int) config('employee_lifecycle.reminders.max_per_run', 150));

        $taskLimit = max(1, (int) floor($maxPerRun * 0.7));
        $eventLimit = max(0, $maxPerRun - $taskLimit);

        return [
            'event_reminders_sent' => $eventLimit > 0 ? $this->sendEventReminders($daysAhead, $cooldownHours, $eventLimit) : 0,
            'task_reminders_sent' => $this->sendTaskReminders($daysAhead, $cooldownHours, $taskLimit),
            'skipped' => false,
        ];
    }

    public function sendTaskReminders(int $daysAhead, int $cooldownHours, int $limit): int
    {
        $rows = DB::table('employee_lifecycle_tasks')
            ->join('employee_lifecycle_events', 'employee_lifecycle_events.id', '=', 'employee_lifecycle_tasks.event_id')
            ->leftJoin('personnels', function ($join): void {
                $join->on('personnels.id', '=', 'employee_lifecycle_events.personnel_id')
                    ->orOn('personnels.tabel_no', '=', 'employee_lifecycle_events.tabel_no');
            })
            ->leftJoin('users as task_owners', 'task_owners.id', '=', 'employee_lifecycle_tasks.owner_user_id')
            ->leftJoin('users as event_owners', 'event_owners.id', '=', 'employee_lifecycle_events.owner_user_id')
            ->whereNotIn('employee_lifecycle_tasks.status', ['completed', 'cancelled'])
            ->whereNotNull('employee_lifecycle_tasks.due_at')
            ->where('employee_lifecycle_tasks.due_at', '<=', now()->addDays($daysAhead)->toDateString())
            ->where(function ($query) use ($cooldownHours): void {
                $query->whereNull('employee_lifecycle_tasks.last_reminder_at')
                    ->orWhere('employee_lifecycle_tasks.last_reminder_at', '<=', now()->subHours($cooldownHours));
            })
            ->where(function ($query): void {
                $query->whereNotNull('employee_lifecycle_tasks.owner_user_id')
                    ->orWhereNotNull('employee_lifecycle_events.owner_user_id');
            })
            ->select([
                'employee_lifecycle_tasks.id',
                'employee_lifecycle_tasks.title',
                'employee_lifecycle_tasks.due_at',
                'employee_lifecycle_tasks.status',
                'employee_lifecycle_tasks.owner_type',
                'employee_lifecycle_tasks.owner_user_id as task_owner_user_id',
                'employee_lifecycle_events.id as event_id',
                'employee_lifecycle_events.title as event_title',
                'employee_lifecycle_events.type as event_type',
                'employee_lifecycle_events.owner_user_id as event_owner_user_id',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
            ])
            ->orderByRaw('CASE WHEN employee_lifecycle_tasks.due_at < ? THEN 0 ELSE 1 END', [today()->toDateString()])
            ->orderBy('employee_lifecycle_tasks.due_at')
            ->limit(max(1, $limit))
            ->get();

        return $this->notifyRows($rows, 'task');
    }

    public function sendEventReminders(int $daysAhead, int $cooldownHours, int $limit): int
    {
        $rows = DB::table('employee_lifecycle_events')
            ->leftJoin('personnels', function ($join): void {
                $join->on('personnels.id', '=', 'employee_lifecycle_events.personnel_id')
                    ->orOn('personnels.tabel_no', '=', 'employee_lifecycle_events.tabel_no');
            })
            ->whereNotIn('employee_lifecycle_events.status', ['completed', 'cancelled'])
            ->whereNotNull('employee_lifecycle_events.deadline_at')
            ->whereNotNull('employee_lifecycle_events.owner_user_id')
            ->where('employee_lifecycle_events.deadline_at', '<=', now()->addDays($daysAhead)->toDateString())
            ->where(function ($query) use ($cooldownHours): void {
                $query->whereNull('employee_lifecycle_events.last_reminder_at')
                    ->orWhere('employee_lifecycle_events.last_reminder_at', '<=', now()->subHours($cooldownHours));
            })
            ->select([
                'employee_lifecycle_events.id',
                'employee_lifecycle_events.title',
                'employee_lifecycle_events.type as event_type',
                'employee_lifecycle_events.status',
                'employee_lifecycle_events.deadline_at',
                'employee_lifecycle_events.owner_user_id',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
            ])
            ->orderByRaw('CASE WHEN employee_lifecycle_events.deadline_at < ? THEN 0 ELSE 1 END', [today()->toDateString()])
            ->orderBy('employee_lifecycle_events.deadline_at')
            ->limit(max(1, $limit))
            ->get();

        return $this->notifyRows($rows, 'event');
    }

    private function notifyRows(Collection $rows, string $kind): int
    {
        $users = User::query()
            ->whereIn('id', $rows->map(fn (object $row): int => $this->recipientId($row, $kind))->filter()->unique())
            ->get()
            ->keyBy('id');

        $sent = 0;

        foreach ($rows as $row) {
            $user = $users->get($this->recipientId($row, $kind));
            if (! $user) {
                continue;
            }

            $payload = $this->payload($row, $kind);

            $user->notify(new PlatformNotification(
                'database',
                $payload,
                $payload['message'],
                $payload['body'],
            ));

            $this->markReminded($row, $kind);
            $sent++;
        }

        return $sent;
    }

    private function recipientId(object $row, string $kind): int
    {
        if ($kind === 'task') {
            return (int) ($row->task_owner_user_id ?: $row->event_owner_user_id);
        }

        return (int) $row->owner_user_id;
    }

    private function payload(object $row, string $kind): array
    {
        $employeeName = $this->personnelName($row);
        $dueAt = $kind === 'task' ? $row->due_at : $row->deadline_at;
        $title = $kind === 'task' ? $row->title : $row->title;
        $eventTitle = $kind === 'task' ? $row->event_title : $row->title;
        $isOverdue = $dueAt < today()->toDateString();

        return [
            'action' => 'employeeLifecycleDeadlineReminder',
            'category' => __('employee-lifecycle::dashboard.title'),
            'message' => $kind === 'task'
                ? __('employee-lifecycle::dashboard.reminders.task_subject')
                : __('employee-lifecycle::dashboard.reminders.event_subject'),
            'name' => $title,
            'body' => $kind === 'task'
                ? __('employee-lifecycle::dashboard.reminders.task_body', [
                    'task' => $title,
                    'event' => $eventTitle,
                    'employee' => $employeeName,
                    'due_at' => $this->dateLabel($dueAt),
                ])
                : __('employee-lifecycle::dashboard.reminders.event_body', [
                    'event' => $eventTitle,
                    'employee' => $employeeName,
                    'due_at' => $this->dateLabel($dueAt),
                ]),
            'kind' => $kind,
            'is_overdue' => $isOverdue,
            'event_type' => $row->event_type,
            'event_id' => $kind === 'task' ? (int) $row->event_id : (int) $row->id,
            'task_id' => $kind === 'task' ? (int) $row->id : null,
            'due_at' => $dueAt,
            'employee' => $employeeName,
            'tabel_no' => (string) $row->tabel_no,
        ];
    }

    private function markReminded(object $row, string $kind): void
    {
        DB::table($kind === 'task' ? 'employee_lifecycle_tasks' : 'employee_lifecycle_events')
            ->where('id', $row->id)
            ->update([
                'last_reminder_at' => now(),
                'reminder_count' => DB::raw('reminder_count + 1'),
                'updated_at' => now(),
            ]);
    }

    private function personnelName(object $row): string
    {
        $name = trim(implode(' ', array_filter([$row->surname, $row->name, $row->patronymic])));

        return $name !== '' ? $name : __('employee-lifecycle::dashboard.labels.unassigned');
    }

    private function dateLabel(?string $date): string
    {
        return $date ? \Illuminate\Support\Carbon::parse($date)->format('d.m.Y') : '—';
    }
}
