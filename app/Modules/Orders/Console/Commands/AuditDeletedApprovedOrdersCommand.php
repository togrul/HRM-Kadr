<?php

namespace App\Modules\Orders\Console\Commands;

use App\Models\OrderLog;
use App\Modules\Orders\Infrastructure\Document\OrderDeletionService;
use Illuminate\Console\Command;

/**
 * Lists soft-deleted orders that are still approved. Before deletion was guarded an
 * approved order could be put in the trash with its HR effect (leave, transfer,
 * termination…) left live. This only reports them — the fix is a human decision:
 * restore the order and cancel it properly, which reverses its effect.
 */
class AuditDeletedApprovedOrdersCommand extends Command
{
    protected $signature = 'orders:audit-deleted-approved {--json : Print the report as JSON}';

    protected $description = 'Report soft-deleted orders that are still approved (read-only)';

    public function handle(OrderDeletionService $deletions): int
    {
        $rows = $deletions->deletedApproved()->map(fn (OrderLog $order): array => [
            'id' => (int) $order->id,
            'order_no' => (string) $order->order_no,
            'type' => (string) (data_get($order->template_snapshot, 'label') ?? $order->order_id ?? '—'),
            'given_date' => (string) ($order->getRawOriginal('given_date') ?? ''),
            'deleted_at' => (string) ($order->getRawOriginal('deleted_at') ?? ''),
            'deleted_by' => (string) data_get($order->personDidDelete, 'name', '—'),
        ])->values()->all();

        if ($this->option('json')) {
            $this->line((string) json_encode(['count' => count($rows), 'orders' => $rows], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($rows === []) {
            $this->info('No soft-deleted approved orders found.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d soft-deleted order(s) are still approved; their effects are live. Restore and cancel them to reverse the effects.', count($rows)));
        $this->table(['ID', 'Order #', 'Type', 'Given', 'Deleted at', 'Deleted by'], $rows);

        return self::SUCCESS;
    }
}
