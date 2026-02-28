<?php

namespace App\Console\Commands;

use App\Models\OrderLog;
use App\Services\Orders\OrderTemplateSnapshotService;
use Illuminate\Console\Command;
use RuntimeException;

class BackfillOrderTemplateSnapshots extends Command
{
    protected $signature = 'orders:templates:backfill-snapshots {--chunk=200 : Chunk size} {--force : Rebuild even when snapshot exists}';

    protected $description = 'Backfill order_logs template snapshot/version fields from current template registry state.';

    public function handle(OrderTemplateSnapshotService $snapshotService): int
    {
        $chunkSize = max(50, (int) $this->option('chunk'));
        $force = (bool) $this->option('force');
        $updated = 0;
        $skipped = 0;

        OrderLog::query()
            ->select(['id', 'order_type_id', 'template_snapshot'])
            ->when(! $force, fn ($query) => $query->whereNull('template_snapshot'))
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (&$updated, &$skipped, $snapshotService): void {
                foreach ($rows as $row) {
                    $orderTypeId = is_numeric($row->order_type_id) ? (int) $row->order_type_id : 0;
                    if ($orderTypeId <= 0) {
                        $skipped++;
                        continue;
                    }

                    try {
                        $snapshot = $snapshotService->capture($orderTypeId);
                    } catch (RuntimeException $exception) {
                        $this->warn("Skipped order_log #{$row->id}: {$exception->getMessage()}");
                        $skipped++;
                        continue;
                    }

                    $row->update([
                        'order_template_version_id' => $snapshot['order_template_version_id'],
                        'template_render_mode' => $snapshot['template_render_mode'],
                        'template_snapshot' => $snapshot['template_snapshot'],
                    ]);

                    $updated++;
                }
            });

        $this->info("Snapshot backfill finished. Updated rows: {$updated}, skipped rows: {$skipped}");

        return self::SUCCESS;
    }
}
