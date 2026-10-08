<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Modules\Orders\Application\Document\DocxTemplateRenderer;
use App\Modules\Orders\Application\Document\DocxToHtmlRenderer;
use App\Modules\Orders\Application\Document\DocxToPdfConverter;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Services\Chief\ChiefResolver;
use App\Support\Language\AzerbaijaniDateFormatter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Turns a composer's input into the filled order document: resolves the template's
 * token values once, then renders them as a temp .docx, an inline PDF preview, or the
 * order's stored authoritative document.
 */
class OrderDocumentBuilder
{
    public function __construct(
        private readonly OrderSubjectResolver $subjects,
        private readonly DocxVariableResolver $resolver,
        private readonly DocxTemplateRenderer $renderer,
        private readonly DocxToPdfConverter $pdf,
        private readonly DocxToHtmlRenderer $html,
        private readonly OrderIssueService $issuer,
        private readonly ChiefResolver $chiefs,
        private readonly AzerbaijaniDateFormatter $dates,
    ) {}

    /**
     * Who signs this order — the permanent chief, or the active temporary delegate
     * (müvəqqəti həvalə) on the order's date. Resolved as-of the order date (not "now")
     * so historical orders name whoever was acting then; falls back to today when the
     * author's free-text date can't be parsed.
     *
     * @return array<string,mixed>
     */
    public function signatory(string $orderDate): array
    {
        return $this->chiefs->current($this->dates->parse($orderDate) ?? now());
    }

    /**
     * The system.* context for this order, keyed by the registry's variable keys.
     *
     * @param  array<string,mixed>  $signatory
     * @return array<string,string>
     */
    public function systemContext(OrderComposition $composition, array $signatory): array
    {
        return [
            'system.order_number' => $composition->orderNumber,
            'system.order_date' => $composition->orderDate,
            'system.organization_city' => $composition->organizationCity,
            'system.organization_name' => app(OrganizationName::class)->current(),
            'system.signatory_full_name' => (string) ($signatory['fullname'] ?? ''),
            'system.signatory_title' => (string) ($signatory['title'] ?? ''),
        ];
    }

    /**
     * The template's token => value map. Pass the signatory when it is also persisted,
     * so the document and the order snapshot name the same person.
     *
     * @param  array<string,mixed>|null  $signatory
     * @return array<string,string>
     */
    public function values(OrderWordTemplate $template, OrderComposition $composition, ?array $signatory = null): array
    {
        return $this->resolver->resolve(
            $template,
            $this->subjects->subject($template, $composition),
            $composition->fields,
            $this->systemContext($composition, $signatory ?? $this->signatory($composition->orderDate)),
        );
    }

    /**
     * Render the filled .docx to a temp file the caller owns.
     *
     * @param  array<string,string>  $values
     */
    public function renderDocx(OrderWordTemplate $template, array $values): string
    {
        return $this->renderer->renderToFile($template->docx_path, $values);
    }

    /**
     * The filled document as a base64 PDF (via LibreOffice), or null when this host
     * has no converter.
     *
     * @param  array<string,string>  $values
     */
    public function renderPdf(OrderWordTemplate $template, array $values): ?string
    {
        $tmp = $this->renderDocx($template, $values);
        $pdfPath = $this->pdf->isAvailable() ? $this->pdf->convert($tmp) : null;
        @unlink($tmp);

        if ($pdfPath === null) {
            return null;
        }

        $pdf = base64_encode((string) file_get_contents($pdfPath));
        @unlink($pdfPath);

        return $pdf;
    }

    /**
     * The filled document as a standalone HTML page — the preview when this host has no
     * PDF converter. Null (and logged) when even that fails.
     *
     * @param  array<string,string>  $values
     */
    public function renderHtml(OrderWordTemplate $template, array $values): ?string
    {
        $tmp = $this->renderDocx($template, $values);

        try {
            return $this->html->render($tmp);
        } catch (RuntimeException $e) {
            Log::warning('orders.preview.render_failed', ['template' => $template->code, 'error' => $e->getMessage()]);

            return null;
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Render the order's filled .docx and store it as the order's authoritative
     * document (served on print). Returns the stored path on the local disk.
     *
     * @param  array<string,string>  $values
     */
    public function store(OrderLog $order, OrderWordTemplate $template, array $values): string
    {
        $tmp = $this->renderDocx($template, $values);

        $stored = 'order-documents/'.$order->id.'.docx';
        Storage::disk('local')->put($stored, (string) file_get_contents($tmp));
        @unlink($tmp);

        $this->issuer->attachUploadedDocx($order, $stored);

        return $stored;
    }
}
