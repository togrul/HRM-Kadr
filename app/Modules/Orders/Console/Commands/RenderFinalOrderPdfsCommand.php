<?php

namespace App\Modules\Orders\Console\Commands;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Modules\Orders\Infrastructure\Document\OrderFinalPdfService;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use Illuminate\Console\Command;

/**
 * Backfills the immutable final PDF of approved Word-engine orders that have none yet —
 * approved before the feature existed, or on a host where LibreOffice was missing or the
 * conversion failed. An existing final copy is never touched.
 */
class RenderFinalOrderPdfsCommand extends Command
{
    protected $signature = 'orders:render-final-pdfs {--limit=0 : Stop after this many orders (0 = all)}';

    protected $description = 'Render and store the final PDF of approved orders that have none yet';

    public function handle(OrderFinalPdfService $finalPdf): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $stored = 0;
        $failed = 0;

        $query = OrderLog::query()
            ->where('status_id', OrderStatusEnum::APPROVED->value)
            ->where('template_render_mode', OrderIssueService::RENDER_MODE_DOCX)
            ->whereNull('final_pdf_path')
            ->orderBy('id');

        foreach ($query->lazyById(100) as $order) {
            if ($limit > 0 && ($stored + $failed) >= $limit) {
                break;
            }

            if ($finalPdf->capture($order) !== null) {
                $stored++;
            } else {
                $failed++;
                $this->warn(sprintf('Order #%d (%s): no final PDF — see the log.', $order->id, $order->order_no));
            }
        }

        $this->info(sprintf('Final PDFs stored: %d; not possible: %d.', $stored, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
