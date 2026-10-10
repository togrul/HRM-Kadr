<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Modules\Orders\Application\Document\DocxTemplateRenderer;
use App\Modules\Orders\Application\Document\DocxToHtmlRenderer;
use App\Modules\Orders\Application\Document\DocxToPdfConverter;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Application\Document\OrderParticipantFields;
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
            // A provisional (not yet assigned) number is never printed.
            'system.order_number' => OrderNumbering::display($composition->orderNumber),
            'system.order_date' => $composition->orderDate,
            'system.organization_city' => $composition->organizationCity,
            'system.organization_name' => app(OrganizationName::class)->current(),
            'system.signatory_full_name' => (string) ($signatory['fullname'] ?? ''),
            'system.signatory_title' => (string) ($signatory['title'] ?? ''),
        ];
    }

    /**
     * The template's token => value map. Pass the signatory when it is also persisted,
     * so the document and the order snapshot name the same person. A multi-participant
     * template also carries the per-participant rows for the renderer
     * (DocxTemplateRenderer::PARTICIPANT_ROWS / PARTICIPANT_ANCHORS).
     *
     * @param  array<string,mixed>|null  $signatory
     * @return array<string,mixed>
     */
    public function values(OrderWordTemplate $template, OrderComposition $composition, ?array $signatory = null): array
    {
        $system = $this->systemContext($composition, $signatory ?? $this->signatory($composition->orderDate));

        if (! $template->isMultiParticipant()) {
            return $this->resolver->resolve($template, $this->subjects->subject($template, $composition), $composition->fields, $system);
        }

        $list = $composition->participantList();
        $people = [];
        foreach ($this->subjects->participants(array_column($list, 'personnel_id')) as $person) {
            $people[(int) $person->id] = $person;
        }

        $participants = array_map(fn (array $participant): array => [
            'personnel' => $people[$participant['personnel_id']] ?? null,
            'fields' => OrderParticipantFields::effective($template, $composition->fields, $participant['fields']),
        ], $list);

        $resolved = $this->resolver->resolveParticipants($template, $participants[0]['personnel'] ?? null, $composition->fields, $participants, $system);

        return $resolved['values'] + [
            DocxTemplateRenderer::PARTICIPANT_ROWS => $resolved['rows'],
            DocxTemplateRenderer::PARTICIPANT_ANCHORS => $template->participantRowTokens(),
        ];
    }

    /**
     * Render the filled .docx to a temp file the caller owns.
     *
     * @param  array<string,mixed>  $values
     */
    public function renderDocx(OrderWordTemplate $template, array $values): string
    {
        return $this->renderer->renderToFile($template->docx_path, $values);
    }

    /**
     * The filled document as a base64 PDF (via LibreOffice), or null when this host
     * has no converter.
     *
     * @param  array<string,mixed>  $values
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
     * @param  array<string,mixed>  $values
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
     * Re-render a pending order's generated document so it carries the number assigned at
     * approval (system.order_number), from the inputs frozen in its snapshot. A document
     * the author uploaded by hand is theirs and is left as it is (logged).
     */
    public function renumber(OrderLog $order, OrderWordTemplate $template): void
    {
        $snapshot = (array) $order->template_snapshot;

        if ((string) ($snapshot['docx_path'] ?? '') !== self::generatedPath($order)) {
            Log::warning('orders.number.uploaded_document_kept', ['order_id' => $order->id, 'order_no' => $order->order_no]);

            return;
        }

        $composition = new OrderComposition(
            presetCode: (string) ($snapshot['template_code'] ?? $template->code),
            personnelId: empty($snapshot['personnel_id']) ? null : (int) $snapshot['personnel_id'],
            candidateId: empty($snapshot['candidate_id']) ? null : (int) $snapshot['candidate_id'],
            hireStructureId: empty($snapshot['hire_structure_id']) ? null : (int) $snapshot['hire_structure_id'],
            hirePositionId: empty($snapshot['hire_position_id']) ? null : (int) $snapshot['hire_position_id'],
            fields: (array) ($snapshot['fields'] ?? []),
            orderNumber: (string) $order->order_no,
            orderDate: (string) ($snapshot['order_date_text'] ?? ''),
            organizationCity: (string) ($snapshot['organization_city'] ?? OrderDraftService::ORGANIZATION_CITY),
            editOrderId: (int) $order->id,
            participants: $order->participants()->get(['personnel_id', 'fields'])
                ->map(fn ($participant): array => ['personnel_id' => (int) $participant->personnel_id, 'fields' => (array) $participant->fields])
                ->all(),
        );

        $signatory = is_array($order->signatory_snapshot) ? $order->signatory_snapshot : null;

        $this->store($order, $template, $this->values($template, $composition, $signatory));
    }

    /** Where the system stores an order's generated document (an uploaded one gets a timestamped name). */
    public static function generatedPath(OrderLog $order): string
    {
        return 'order-documents/'.$order->id.'.docx';
    }

    /**
     * Render the order's filled .docx and store it as the order's authoritative
     * document (served on print). Returns the stored path on the local disk.
     *
     * @param  array<string,mixed>  $values
     */
    public function store(OrderLog $order, OrderWordTemplate $template, array $values): string
    {
        $tmp = $this->renderDocx($template, $values);

        $stored = self::generatedPath($order);
        Storage::disk('local')->put($stored, (string) file_get_contents($tmp));
        @unlink($tmp);

        $this->issuer->attachUploadedDocx($order, $stored);

        return $stored;
    }
}
