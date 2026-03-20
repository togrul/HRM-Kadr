<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Models\PersonnelMediaMention;
use App\Modules\Personnel\Application\Services\ProfessionalPortfolioLinkHealthService;
use Illuminate\Console\Command;

class ProfessionalPortfolioCheckMediaLinksCommand extends Command
{
    protected $signature = 'personnel:portfolio-check-media-links
        {--limit=100 : Max records to scan}
        {--personnel-id= : Restrict to one personnel id}
        {--json : Print report as JSON}';

    protected $description = 'Check Professional Portfolio media links and archive health.';

    public function handle(ProfessionalPortfolioLinkHealthService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $personnelId = $this->option('personnel-id');

        $query = PersonnelMediaMention::query()
            ->with('archiveAttachment:id,file_path,disk')
            ->when($personnelId, fn ($builder) => $builder->where('personnel_id', (int) $personnelId))
            ->where(function ($builder) {
                $builder->whereNotNull('url')
                    ->orWhereIn('verification_status', [
                        PersonnelMediaMention::STATUS_BROKEN_LINK,
                        PersonnelMediaMention::STATUS_ARCHIVED_ONLY,
                    ]);
            })
            ->orderBy('id')
            ->limit($limit);

        $checked = 0;
        $broken = 0;
        $archiveMissing = 0;
        $statusUpdates = 0;

        foreach ($query->get() as $record) {
            $previousStatus = $record->verification_status;
            $result = $service->check($record);
            $checked++;
            $broken += (int) ($result['link_status'] === 'broken');
            $archiveMissing += (int) ($result['archive_status'] === 'missing');
            $statusUpdates += (int) ($result['verification_status'] !== $previousStatus);
        }

        $payload = [
            'checked' => $checked,
            'broken_links' => $broken,
            'missing_archives' => $archiveMissing,
            'status_updates' => $statusUpdates,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $this->info("Checked: {$checked}");
            $this->line("Broken links: {$broken}");
            $this->line("Missing archives: {$archiveMissing}");
            $this->line("Status updates: {$statusUpdates}");
        }

        return self::SUCCESS;
    }
}
