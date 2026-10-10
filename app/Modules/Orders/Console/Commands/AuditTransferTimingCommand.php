<?php

namespace App\Modules\Orders\Console\Commands;

use App\Modules\Orders\Application\Services\TransferTimingAudit;
use Illuminate\Console\Command;

class AuditTransferTimingCommand extends Command
{
    protected $signature = 'orders:audit-transfer-timing {--order-id= : Inspect a single order ID}';

    protected $description = 'Read-only transfer timing audit; emits JSON Lines without changing employee records';

    public function handle(TransferTimingAudit $audit): int
    {
        $orderId = $this->option('order-id');
        if ($orderId !== null && (filter_var($orderId, FILTER_VALIDATE_INT) === false || (int) $orderId < 1)) {
            $this->line(json_encode(['error' => 'invalid_order_id'], JSON_THROW_ON_ERROR));

            return self::INVALID;
        }

        $counts = [];
        foreach ($audit->findings($orderId !== null ? (int) $orderId : null) as $finding) {
            $classification = $finding['classification'];
            $counts[$classification] = ($counts[$classification] ?? 0) + 1;
            $this->line(json_encode(['type' => 'finding', ...$finding], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        }

        $this->line(json_encode(['type' => 'summary', 'read_only' => true, 'count' => array_sum($counts), 'classifications' => (object) $counts], JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
