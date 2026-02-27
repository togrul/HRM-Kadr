<?php

namespace App\Console\Commands;

use App\Models\OrderLog;
use App\Models\OrderType;
use App\Services\Orders\OrderTemplateSnapshotService;
use Illuminate\Console\Command;

class BackfillOrderTemplateSnapshots extends Command
{
    protected $signature = 'orders:templates:backfill-snapshots {--chunk=200 : Chunk size} {--force : Rebuild even when snapshot exists}';

    protected $description = 'Backfill order_logs template snapshot/version fields from current template registry state.';

    public function handle(OrderTemplateSnapshotService $snapshotService): int
    {
        $chunkSize = max(50, (int) $this->option('chunk'));
        $force = (bool) $this->option('force');
        $updated = 0;
        $legacyPathByOrderType = [];

        OrderLog::query()
            ->select(['id', 'order_type_id', 'template_snapshot'])
            ->when(! $force, fn ($query) => $query->whereNull('template_snapshot'))
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (&$updated, $snapshotService): void {
                foreach ($rows as $row) {
                    $orderTypeId = is_numeric($row->order_type_id) ? (int) $row->order_type_id : 0;
                    $legacyTemplatePath = '';

                    if ($orderTypeId > 0) {
                        if (! array_key_exists($orderTypeId, $legacyPathByOrderType)) {
                            $legacyPathByOrderType[$orderTypeId] = (string) optional(
                                OrderType::query()
                                    ->with('order:id,content')
                                    ->find($orderTypeId)
                                    ?->order
                            )->content;
                        }

                        $legacyTemplatePath = (string) ($legacyPathByOrderType[$orderTypeId] ?? '');
                    }

                    $snapshot = $snapshotService->capture($orderTypeId, $legacyTemplatePath);

                    $row->update([
                        'order_template_version_id' => $snapshot['order_template_version_id'],
                        'template_render_mode' => $snapshot['template_render_mode'],
                        'template_snapshot' => $snapshot['template_snapshot'],
                    ]);

                    $updated++;
                }
            });

        $this->info("Snapshot backfill finished. Updated rows: {$updated}");

        return self::SUCCESS;
    }
}
