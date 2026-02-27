<?php

namespace App\Console\Commands;

use App\Models\OrderTemplateSet;
use App\Services\Orders\OrderTemplateVersionLifecycleService;
use Illuminate\Console\Command;

class ReconcileOrderTemplateActives extends Command
{
    protected $signature = 'orders:templates:reconcile-actives';

    protected $description = 'Ensure each order template set has only one active version.';

    public function handle(OrderTemplateVersionLifecycleService $lifecycleService): int
    {
        $fixed = 0;
        $total = 0;

        OrderTemplateSet::query()
            ->select('id', 'order_type_id')
            ->orderBy('id')
            ->chunkById(200, function ($sets) use ($lifecycleService, &$fixed, &$total): void {
                foreach ($sets as $set) {
                    $total++;

                    $beforeActiveIds = $set->versions()
                        ->where('is_active', true)
                        ->pluck('id')
                        ->values()
                        ->all();

                    $winner = $lifecycleService->reconcileSingleActiveForSet((int) $set->id, auth()->id());

                    $afterActiveIds = $set->versions()
                        ->where('is_active', true)
                        ->pluck('id')
                        ->values()
                        ->all();

                    $changed = $beforeActiveIds !== $afterActiveIds;
                    if ($changed) {
                        $fixed++;
                    }

                    $winnerId = $winner?->id ?? null;
                    $this->line("set={$set->id} type={$set->order_type_id} winner=".($winnerId ?? '-')." changed=".($changed ? 'yes' : 'no'));
                }
            });

        $this->newLine();
        $this->info("Reconciled sets: {$total}, fixed: {$fixed}");

        return self::SUCCESS;
    }
}

