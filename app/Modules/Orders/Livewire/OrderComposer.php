<?php

namespace App\Modules\Orders\Livewire;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Services\Orders\Document\OrderDocumentDocxRenderer;
use App\Services\Orders\Document\OrderFieldTransformer;
use App\Services\Orders\Document\OrderHtmlToDocxRenderer;
use App\Services\Orders\Document\OrderIssueService;
use App\Services\Orders\Document\OrderRenderService;
use App\Services\Orders\Document\OrderTemplateCompiler;
use App\Services\Orders\Document\OrderTemplateProvider;
use App\Services\Orders\Document\TemplateFieldSchema;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Issue-time order composer: pick a template + personnel + fields, see a live
 * preview, correct the text inline, then download the finalized .docx.
 *
 * This is the user-facing surface of the redesigned engine — it wires the
 * OrderRenderService (preview → finalize) and the OrderTemplatePresets library to
 * the HR user. The inline editing happens on $editedHtml (the contenteditable
 * preview); finalize() freezes exactly that HTML into the document.
 */
class OrderComposer extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public string $presetCode = '';

    public ?int $personnelId = null;

    public string $personnelQuery = '';

    public ?string $personnelLabel = null;

    /** @var array<string,string> */
    public array $fields = [];

    public string $orderNumber = '';

    public string $orderDate = '';

    public string $organizationCity = 'Bakı şəhəri';

    public string $previewHtml = '';

    public string $editedHtml = '';

    /**
     * True once the HR user has typed into the preview. When false, the .docx is
     * rendered from the AST (faithful Word formatting); when true, from the edited
     * HTML so manual corrections are preserved.
     */
    public bool $manuallyEdited = false;

    /** Set when editing an existing pending block order (its order_logs id). */
    public ?int $editOrderId = null;

    /** A corrected .docx the user uploaded to replace the generated document. */
    public $uploadedDocx = null;

    public bool $hasUploadedDocx = false;

    public function mount(?string $presetCode = null, ?int $personnelId = null, ?int $orderId = null): void
    {
        $this->authorize('add-orders');

        // Edit mode: re-open an existing pending block order and prefill everything
        // from its frozen snapshot so the HR user can correct fields or the text.
        // Keyed by id (order numbers may contain "/", which a path param can't carry).
        if ($orderId !== null) {
            $this->loadForEdit($orderId);

            return;
        }

        // Nullable + coalesced: as a full-page route component Livewire may pass null
        // for params not present in the route ({personnelId?}), so guard the defaults.
        $this->presetCode = $presetCode ?? '';
        $this->personnelId = $personnelId;

        if ($personnelId) {
            $this->personnelLabel = optional(Personnel::find($personnelId))->fullname;
        }
    }

    public function isEditing(): bool
    {
        return $this->editOrderId !== null;
    }

    private function loadForEdit(int $orderId): void
    {
        $order = OrderLog::find($orderId);

        abort_if($order === null, 404);
        abort_unless((string) $order->template_render_mode === OrderIssueService::RENDER_MODE, 404);
        // Approved orders already produced their HR side-effects — not editable.
        abort_unless((int) $order->status_id === OrderIssueService::STATUS_PENDING, 403);

        $snapshot = $order->template_snapshot ?? [];

        $this->editOrderId = $order->id;
        $this->presetCode = (string) ($snapshot['template_code'] ?? '');
        $this->fields = (array) ($snapshot['fields'] ?? []);
        $this->orderNumber = (string) $order->order_no;
        $this->orderDate = (string) ($snapshot['order_date_text'] ?? '');
        $this->previewHtml = (string) ($snapshot['html'] ?? '');
        $this->editedHtml = $this->previewHtml;
        $this->manuallyEdited = (bool) ($snapshot['edited'] ?? false);
        $this->hasUploadedDocx = ! empty($snapshot['docx_path']);

        $personnelId = $snapshot['personnel_id'] ?? null;
        if ($personnelId) {
            $this->personnelId = (int) $personnelId;
            $this->personnelLabel = optional(Personnel::find($this->personnelId))->fullname;
        }
    }

    /**
     * Replace this pending order's document with a user-corrected .docx. The file
     * is stored verbatim and served on every future print/download until the text
     * is inline-edited again (which reverts to the generated document).
     */
    public function uploadDocx(OrderIssueService $issuer): void
    {
        $this->authorize('add-orders');

        if (! $this->isEditing()) {
            return;
        }

        $this->validate([
            'uploadedDocx' => ['required', 'file', 'mimes:docx,doc', 'max:10240'],
        ], [], ['uploadedDocx' => __('orders::order_composer.labels.replace_word')]);

        $order = OrderLog::findOrFail($this->editOrderId);
        $path = $this->uploadedDocx->storeAs(
            'order-documents',
            $order->id.'-'.now()->timestamp.'.docx'
        );

        $issuer->attachUploadedDocx($order, $path);

        $this->uploadedDocx = null;
        $this->hasUploadedDocx = true;

        $this->dispatch('orderAdded', __('orders::order_composer.messages.word_replaced'));
    }

    /**
     * Searchable personnel picker — an HR user finds the employee by name or tabel
     * number instead of needing an id in the URL.
     *
     * @return array<int,array{id:int,label:string}>
     */
    public function getPersonnelResultsProperty(): array
    {
        $term = trim($this->personnelQuery);
        if (mb_strlen($term) < 2) {
            return [];
        }

        return Personnel::query()
            ->active()
            ->where(fn ($q) => $q->nameLike($term)->orWhere('tabel_no', 'like', "%{$term}%"))
            ->orderBy('surname')
            ->limit(8)
            ->get(['id', 'surname', 'name', 'patronymic', 'tabel_no'])
            ->map(fn (Personnel $p) => [
                'id' => $p->id,
                'label' => trim("{$p->surname} {$p->name} {$p->patronymic}")." ({$p->tabel_no})",
            ])
            ->all();
    }

    public function selectPersonnel(int $id): void
    {
        $personnel = Personnel::find($id);
        if ($personnel) {
            $this->personnelId = $personnel->id;
            $this->personnelLabel = $personnel->fullname;
        }
        $this->personnelQuery = '';
        $this->previewHtml = '';
        $this->editedHtml = '';
    }

    public function clearPersonnel(): void
    {
        $this->personnelId = null;
        $this->personnelLabel = null;
        $this->previewHtml = '';
    }

    /**
     * @return array<string,string>
     */
    public function getPresetsProperty(OrderTemplateProvider $templates): array
    {
        return $templates->available();
    }

    /**
     * The input fields the selected template needs — auto-derived from its field.*
     * placeholders, so the form always matches the template.
     *
     * @return array<int,array{key:string,placeholder:string,label:string,type:string}>
     */
    public function getFieldDefsProperty(OrderTemplateProvider $templates, TemplateFieldSchema $schema): array
    {
        if ($this->presetCode === '') {
            return [];
        }

        return $schema->for($templates->blocks($this->presetCode));
    }

    public function updatedPresetCode(): void
    {
        // Switching templates clears any field values from the previous one.
        $this->fields = [];
        $this->previewHtml = '';
        $this->editedHtml = '';
    }

    public function generatePreview(OrderTemplateProvider $templates, OrderRenderService $renderer): void
    {
        $this->authorize('add-orders');

        $blocks = $templates->blocks($this->presetCode);
        if ($blocks === []) {
            $this->addError('presetCode', __('orders::order_composer.errors.unknown_type'));

            return;
        }

        if (! $this->personnelId) {
            $this->addError('personnelId', __('orders::order_composer.errors.personnel_required'));

            return;
        }

        $this->previewHtml = $renderer->previewBlocks($blocks, $this->context());
        $this->editedHtml = $this->previewHtml;
    }

    public function issue(OrderRenderService $renderer, OrderIssueService $issuer, OrderTemplateProvider $templates)
    {
        $this->authorize('add-orders');

        $blocks = $templates->blocks($this->presetCode);
        $context = $this->context();
        // The clean, AST-generated HTML (used unless the user typed corrections).
        $generatedHtml = $blocks !== [] ? $renderer->previewBlocks($blocks, $context) : '';

        $html = $this->manuallyEdited && trim($this->editedHtml) !== '' ? $this->editedHtml : $generatedHtml;
        if ($html === '') {
            $this->addError('previewHtml', __('orders::order_composer.errors.nothing_to_generate'));

            return null;
        }
        if (trim($this->orderNumber) === '') {
            $this->addError('orderNumber', __('orders::order_composer.errors.number_required'));

            return null;
        }

        $payload = [
            'template_code' => $this->presetCode,
            'label' => $templates->available()[$this->presetCode] ?? $this->presetCode,
            'personnel_id' => $this->personnelId,
            'fields' => $this->fields,
            'order_number' => trim($this->orderNumber),
            'order_date' => $this->orderDate,
            'snapshot_html' => $html,
            'edited' => $this->manuallyEdited,
        ];

        // Edit mode: re-freeze the existing pending order in place. orderAdded closes
        // the side modal and refreshes the list (no new row, no download).
        if ($this->isEditing()) {
            $order = OrderLog::findOrFail($this->editOrderId);
            $issuer->update($order, $payload);
            $this->renderAndStoreDocx($order, $blocks, $context);

            $this->dispatch('orderAdded', __('orders::order_composer.messages.order_updated'));

            return null;
        }

        $order = $issuer->issue($payload);
        $stored = $this->renderAndStoreDocx($order, $blocks, $context);

        // Close the modal + refresh the list, and stream the finalized .docx down.
        $this->dispatch('orderAdded', __('orders::order_composer.messages.order_issued'));

        return \Illuminate\Support\Facades\Storage::disk('local')->download($stored, $this->downloadName());
    }

    /**
     * Render the order's .docx and store it as the order's authoritative document.
     * A clean order renders from the AST (faithful Times New Roman / bold / justified
     * Word formatting); a manually-corrected one renders from the edited HTML so the
     * user's inline changes are preserved.
     *
     * @param  TemplateBlock[]  $blocks
     * @param  array<string,mixed>  $context
     */
    private function renderAndStoreDocx(OrderLog $order, array $blocks, array $context): string
    {
        if ($this->manuallyEdited && trim($this->editedHtml) !== '') {
            $tmp = app(OrderHtmlToDocxRenderer::class)->renderToFile($this->editedHtml);
        } else {
            $document = app(OrderTemplateCompiler::class)->compileBlocks($blocks, $context);
            $tmp = app(OrderDocumentDocxRenderer::class)->renderToFile($document);
        }

        $stored = 'order-documents/'.$order->id.'.docx';
        \Illuminate\Support\Facades\Storage::disk('local')->put($stored, (string) file_get_contents($tmp));
        @unlink($tmp);

        app(OrderIssueService::class)->attachUploadedDocx($order, $stored);

        return $stored;
    }

    public function render()
    {
        return view('orders::livewire.orders.order-composer');
    }

    /**
     * @return array<string,mixed>
     */
    private function context(): array
    {
        $fields = app(OrderFieldTransformer::class)->transform($this->fields);

        // Structured fields (structure/position) submit an id used by the side-effect;
        // for the DOCUMENT show the name instead. The raw id stays in $this->fields
        // (and the snapshot) for approval.
        foreach ($this->fieldDefs as $def) {
            if (in_array($def['type'], ['structure', 'position'], true) && ! empty($fields[$def['key']])) {
                $fields[$def['key']] = $this->structuredName($def['type'], (int) $fields[$def['key']]);
            }
        }

        return [
            'personnel' => $this->personnelId
                ? Personnel::with(['structure:id,name', 'position:id,name'])->find($this->personnelId)
                : null,
            'fields' => $fields,
            'order_number' => $this->orderNumber,
            'order_date' => $this->orderDate,
            'system' => ['organization_city' => $this->organizationCity],
        ];
    }

    /**
     * @return array<int,string>
     */
    public function getStructureOptionsProperty(): array
    {
        return \App\Models\Structure::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return array<int,string>
     */
    public function getPositionOptionsProperty(): array
    {
        return \App\Models\Position::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    private function structuredName(string $type, int $id): string
    {
        $name = match ($type) {
            'structure' => optional(\App\Models\Structure::find($id))->name,
            'position' => optional(\App\Models\Position::find($id))->name,
            default => null,
        };

        return (string) ($name ?: $id);
    }

    private function downloadName(): string
    {
        $code = $this->presetCode !== '' ? $this->presetCode : 'order';
        $number = $this->orderNumber !== '' ? '_'.$this->orderNumber : '';

        // "/" and "\" are illegal in download filenames (order numbers may carry them).
        return str_replace(['/', '\\'], '-', $code.$number).'.docx';
    }
}
