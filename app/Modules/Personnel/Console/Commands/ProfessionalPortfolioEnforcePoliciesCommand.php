<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Models\PersonnelEventRecord;
use App\Models\PersonnelMediaMention;
use App\Models\PersonnelProjectRecord;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioWorkflowPolicyService;
use Illuminate\Console\Command;

class ProfessionalPortfolioEnforcePoliciesCommand extends Command
{
    protected $signature = 'personnel:portfolio-enforce-policies {--json : Print report as JSON}';

    protected $description = 'Enforce stricter professional portfolio workflow and media health policies.';

    public function handle(): int
    {
        $report = [
            'media_status_updates' => $this->enforceMediaPolicies(),
            'stale_pending_rejections' => $this->rejectStalePendingRecords(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $this->table(['Metric', 'Value'], collect($report)->map(fn ($value, $key) => [$key, $value])->all());
        }

        return self::SUCCESS;
    }

    private function enforceMediaPolicies(): int
    {
        $service = app(ProfessionalPortfolioWorkflowPolicyService::class);
        $updated = 0;

        PersonnelMediaMention::query()
            ->with('archiveAttachment')
            ->whereIn('verification_status', [
                PersonnelMediaMention::STATUS_VERIFIED,
                PersonnelMediaMention::STATUS_BROKEN_LINK,
                PersonnelMediaMention::STATUS_ARCHIVED_ONLY,
            ])
            ->chunkById(100, function ($records) use ($service, &$updated) {
                foreach ($records as $record) {
                    $recommended = $service->recommendedMediaStatus($record);
                    if ($recommended === null || $recommended === $record->verification_status) {
                        continue;
                    }

                    $record->forceFill([
                        'verification_status' => $recommended,
                        'notes' => $this->appendPolicyNote($record->notes, __('personnel::portfolio.messages.policy_note_media_status', [
                            'status' => __('personnel::portfolio.status.'.$recommended),
                        ])),
                    ])->save();

                    $updated++;
                }
            });

        return $updated;
    }

    private function rejectStalePendingRecords(): int
    {
        if (! (bool) config('personnel.portfolio.policy.auto_reject_stale_pending', false)) {
            return 0;
        }

        $days = max(1, (int) config('personnel.portfolio.policy.stale_pending_days', 30));
        $cutoff = now()->subDays($days);
        $updated = 0;

        foreach ([
            PersonnelEventRecord::class,
            PersonnelMediaMention::class,
            PersonnelProjectRecord::class,
        ] as $modelClass) {
            $modelClass::query()
                ->where('verification_status', 'pending')
                ->where('created_at', '<=', $cutoff)
                ->chunkById(100, function ($records) use (&$updated) {
                    foreach ($records as $record) {
                        $record->forceFill([
                            'verification_status' => 'rejected',
                            'verified_at' => now(),
                            'notes' => $this->appendPolicyNote($record->notes, __('personnel::portfolio.messages.policy_note_stale_pending')),
                        ])->save();
                        $updated++;
                    }
                });
        }

        return $updated;
    }

    private function appendPolicyNote(?string $existing, string $message): string
    {
        $prefix = '['.now()->format('d.m.Y H:i').'] ';

        return trim(collect([$existing, $prefix.$message])->filter()->implode(PHP_EOL));
    }
}
