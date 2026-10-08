<?php

namespace App\Modules\Orders\Livewire;

use App\Models\OrderLog;
use App\Modules\Orders\Application\Document\DocxToHtmlRenderer;
use App\Modules\Orders\Application\Document\DocxToPdfConverter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Read-only side-panel preview of one order from the list: its header facts plus the
 * stored .docx rendered as an inline PDF (loaded after the panel opens, since the
 * LibreOffice conversion takes a moment), or as HTML when the host has no LibreOffice.
 */
class OrderPreview extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $orderId;

    /** Base64 PDF of the stored document, filled by loadPdf(). */
    public string $pdf = '';

    /**
     * The stored document as a standalone HTML page — the fallback when this host
     * cannot produce the PDF (no LibreOffice), so the preview still shows the order.
     */
    #[Locked]
    public string $html = '';

    public bool $pdfLoaded = false;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
        $this->authorize('view', $this->order());
    }

    public function loadPdf(DocxToPdfConverter $converter, DocxToHtmlRenderer $htmlRenderer): void
    {
        $this->pdfLoaded = true;

        $docxPath = (string) data_get($this->order()->template_snapshot, 'docx_path', '');
        if ($docxPath === '') {
            return;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($docxPath)) {
            Log::warning('orders.preview.document_missing', ['order_id' => $this->orderId, 'docx_path' => $docxPath]);

            return;
        }

        $absolute = $disk->path($docxPath);

        $pdfPath = $converter->isAvailable() ? $converter->convert($absolute) : null;
        if ($pdfPath !== null) {
            $this->pdf = base64_encode((string) file_get_contents($pdfPath));
            @unlink($pdfPath);

            return;
        }

        try {
            $this->html = $htmlRenderer->render($absolute);
        } catch (Throwable $e) {
            Log::warning('orders.preview.render_failed', [
                'order_id' => $this->orderId,
                'docx_path' => $docxPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function order(): OrderLog
    {
        return OrderLog::withTrashed()->with(['order:id,name', 'status:id,name'])->findOrFail($this->orderId);
    }

    public function render(): View
    {
        return view('orders::livewire.orders.order-preview', ['order' => $this->order()]);
    }
}
