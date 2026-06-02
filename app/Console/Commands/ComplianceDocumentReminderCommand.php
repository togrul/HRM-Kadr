<?php

namespace App\Console\Commands;

use App\Modules\Compliance\Application\Services\DocumentExpiryReadService;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
use Illuminate\Console\Command;

class ComplianceDocumentReminderCommand extends Command
{
    protected $signature = 'compliance:document-reminders
        {--days= : Override reminder window}
        {--notify : Create and dispatch an HR/admin notification campaign}
        {--json : Output machine-readable payload}';

    protected $description = 'Report expired, missing and soon-expiring personnel compliance documents.';

    public function handle(DocumentExpiryReadService $service, NotificationCampaignDispatcher $dispatcher): int
    {
        $days = (int) ($this->option('days') ?: config('compliance.document_expiry.reminders.days_ahead', 30));
        $rows = $service->reminderRows($days);
        $notification = null;

        if ($this->option('notify') && $rows->isNotEmpty()) {
            $notification = $dispatcher->createManualCampaign([
                'category' => 'announcement',
                'title' => __('compliance::documents.reminders.notification_title', ['count' => $rows->count()]),
                'body' => $this->notificationBody($rows->take(12)->values()->all(), $rows->count()),
                'channel' => 'database',
                'audience_targets' => ['hr', 'admins'],
                'send_now' => true,
            ]);
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'days_ahead' => $days,
                'count' => $rows->count(),
                'notification_campaign_id' => $notification?->id,
                'rows' => $rows->values()->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Compliance reminder window: {$days} days");
        $this->table(
            ['Personnel', 'Tabel', 'Document', 'Expires', 'Days left', 'Status'],
            $rows->map(fn (array $row): array => [
                $row['personnel_name'],
                $row['tabel_no'],
                $row['document_label'],
                $row['expires_at'],
                $row['days_left'] ?? '-',
                $row['status'],
            ])->all()
        );

        if ($notification) {
            $this->info(__('compliance::documents.reminders.notification_created', ['id' => $notification->id]));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function notificationBody(array $rows, int $total): string
    {
        $lines = collect($rows)->map(fn (array $row): string => sprintf(
            '%s · %s · %s · %s',
            $row['personnel_name'],
            $row['tabel_no'],
            $row['document_label'],
            $row['status']
        ));

        if ($total > count($rows)) {
            $lines->push(__('compliance::documents.reminders.more_items', ['count' => $total - count($rows)]));
        }

        return $lines->implode("\n");
    }
}
