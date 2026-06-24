<?php

namespace App\Modules\Compliance\Application\Services;

use App\Models\Personnel;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Collection;

/**
 * Turns the compliance expiry rows into per-recipient reminders: each affected
 * employee receives a digest of their own at-risk documents, and their manager
 * (parent structure head) is escalated for the configured serious statuses
 * (expired/missing). This runs alongside the HR/admin digest campaign so the
 * burden lands on the people who can actually fix the document, not just HR.
 */
class ComplianceReminderNotifier
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows  DocumentExpiryReadService::reminderRows() output
     * @return array{employees: int, managers: int}
     */
    public function notify(Collection $rows): array
    {
        $byTabel = $rows
            ->filter(fn (array $row): bool => filled($row['tabel_no'] ?? null))
            ->groupBy('tabel_no');

        if ($byTabel->isEmpty()) {
            return ['employees' => 0, 'managers' => 0];
        }

        $personnel = Personnel::query()
            ->whereIn('tabel_no', $byTabel->keys()->all())
            ->get(['id', 'tabel_no', 'email', 'parent_id'])
            ->keyBy('tabel_no');

        $managerById = Personnel::query()
            ->whereIn('id', $personnel->pluck('parent_id')->filter()->unique()->all())
            ->get(['id', 'email'])
            ->keyBy('id');

        $userByEmail = User::query()
            ->whereIn('email', $personnel->pluck('email')->merge($managerById->pluck('email'))->filter()->unique()->all())
            ->where('is_active', true)
            ->get()
            ->keyBy('email');

        $notifyEmployee = (bool) config('compliance.document_expiry.reminders.notify_employee', true);
        $escalateStatuses = (array) config('compliance.document_expiry.reminders.escalate_manager_statuses', ['expired', 'missing']);

        $employees = 0;
        $managerBuckets = [];

        foreach ($byTabel as $tabel => $docRows) {
            $person = $personnel->get($tabel);
            if (! $person) {
                continue;
            }

            if ($notifyEmployee && $person->email && ($employeeUser = $userByEmail->get($person->email))) {
                $employeeUser->notify($this->employeeNotification($docRows));
                $employees++;
            }

            $escalations = $docRows->filter(fn (array $row): bool => in_array($row['status'] ?? '', $escalateStatuses, true));
            $managerEmail = $person->parent_id ? $managerById->get($person->parent_id)?->email : null;
            if ($escalations->isNotEmpty() && $managerEmail) {
                $managerBuckets[$managerEmail] = array_merge(
                    $managerBuckets[$managerEmail] ?? [],
                    $escalations->map(fn (array $row): string => sprintf(
                        '%s · %s · %s',
                        $row['personnel_name'],
                        $row['document_label'],
                        $row['status'],
                    ))->all(),
                );
            }
        }

        $managers = 0;
        foreach ($managerBuckets as $email => $lines) {
            $manager = $userByEmail->get($email);
            if (! $manager) {
                continue;
            }
            $manager->notify($this->managerNotification($lines));
            $managers++;
        }

        return ['employees' => $employees, 'managers' => $managers];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $docRows
     */
    private function employeeNotification(Collection $docRows): PlatformNotification
    {
        $subject = __('compliance::documents.reminders.employee_subject', ['count' => $docRows->count()]);
        $body = $docRows->map(function (array $row): string {
            $days = (is_int($row['days_left'] ?? null)) ? ' ('.$row['days_left'].')' : '';

            return sprintf('%s · %s%s', $row['document_label'], $row['status'], $days);
        })->implode("\n");

        return new PlatformNotification('database', [
            'type' => 'compliance_document_self',
            'message' => $subject,
            'body' => $body,
            'count' => $docRows->count(),
        ], $subject, $body);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function managerNotification(array $lines): PlatformNotification
    {
        $subject = __('compliance::documents.reminders.manager_subject', ['count' => count($lines)]);
        $body = implode("\n", $lines);

        return new PlatformNotification('database', [
            'type' => 'compliance_document_escalation',
            'message' => $subject,
            'body' => $body,
            'count' => count($lines),
        ], $subject, $body);
    }
}
