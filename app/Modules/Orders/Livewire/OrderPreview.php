<?php

namespace App\Modules\Orders\Livewire;

use App\Models\OrderLog;
use App\Modules\Orders\Application\Document\DocxToPdfConverter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Read-only side-panel preview of one order from the list: its header facts plus the
 * stored .docx rendered as an inline PDF (loaded after the panel opens, since the
 * LibreOffice conversion takes a moment).
 */
class OrderPreview extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $orderId;

    /** Base64 PDF of the stored document, filled by loadPdf(). */
    public string $pdf = '';

    public bool $pdfLoaded = false;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
        $this->authorize('view', $this->order());
    }

    public function loadPdf(DocxToPdfConverter $converter): void
    {
        $this->pdfLoaded = true;

        $docxPath = (string) data_get($this->order()->template_snapshot, 'docx_path', '');
        if ($docxPath === '' || ! Storage::disk('local')->exists($docxPath)) {
            return;
        }

        $pdfPath = $converter->convert(Storage::disk('local')->path($docxPath));
        if ($pdfPath === null) {
            return;
        }

        $this->pdf = base64_encode((string) file_get_contents($pdfPath));
        @unlink($pdfPath);
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
