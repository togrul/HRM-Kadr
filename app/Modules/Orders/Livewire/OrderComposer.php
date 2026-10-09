<?php

namespace App\Modules\Orders\Livewire;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Application\Document\OrderLeaveDateRules;
use App\Modules\Orders\Application\Document\OrderTemplateProvider;
use App\Modules\Orders\Infrastructure\Document\OrderCompositionIssuer;
use App\Modules\Orders\Infrastructure\Document\OrderDocumentBuilder;
use App\Modules\Orders\Infrastructure\Document\OrderDraftService;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderLookupFieldRegistry;
use App\Modules\Orders\Infrastructure\Document\OrderNumbering;
use App\Modules\Orders\Infrastructure\Document\OrderSubjectResolver;
use App\Modules\Orders\Livewire\Concerns\InteractsWithOrderSubjectPicker;
use App\Support\Language\AzerbaijaniDateFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Issue-time order composer for the Word-upload engine: the HR user picks an order
 * type (an uploaded Word template), picks the employee, fills the per-order manual
 * fields, and the system fills the template's ${tokens} with resolved data to produce
 * the final .docx. Automatic variables (employee/system) resolve behind the scenes;
 * the author only types the "manual" fields the template declared.
 */
class OrderComposer extends Component
{
    use AuthorizesRequests, InteractsWithOrderSubjectPicker, WithFileUploads;

    /**
     * Nullable only because Livewire assigns a same-named mount param to the property
     * before mount() runs: a caller passing null (no preset) used to throw a TypeError.
     * mount() normalises it to '' right away.
     */
    public ?string $presetCode = '';

    /** @var array<string,mixed> manual field key => value */
    public array $fields = [];

    public string $orderNumber = '';

    /** The order date as the native date input sends it (Y-m-d). */
    public string $orderDate = '';

    public string $organizationCity = OrderDraftService::ORGANIZATION_CITY;

    /** Set when editing an existing pending docx order (its order_logs id). */
    #[Locked]
    public ?int $editOrderId = null;

    /** A corrected .docx the user uploaded to replace the generated document. */
    public $uploadedDocx = null;

    public bool $hasUploadedDocx = false;

    /** Base64 of the generated PDF, shown inline as a faithful preview. */
    public string $previewPdf = '';

    /** The generated document as HTML — the preview when this host has no LibreOffice. */
    #[Locked]
    public string $previewHtml = '';

    /** Per-request cache of the selected template (private → not persisted by Livewire). */
    private ?OrderWordTemplate $templateCache = null;

    private bool $templateLoaded = false;

    public function mount(?string $presetCode = null, ?int $personnelId = null, ?int $orderId = null): void
    {
        $this->authorize('add-orders');

        if ($orderId !== null) {
            $this->loadForEdit($orderId);

            return;
        }

        $this->presetCode = $presetCode ?? '';
        $this->applyFieldDefaults();
        $this->orderDate = now()->format('Y-m-d');
        $this->pickPersonnel($personnelId);
    }

    public function isEditing(): bool
    {
        return $this->editOrderId !== null;
    }

    private function loadForEdit(int $orderId): void
    {
        $order = OrderLog::find($orderId);

        abort_if($order === null, 404);
        abort_unless((string) $order->template_render_mode === OrderIssueService::RENDER_MODE_DOCX, 404);
        abort_unless((int) $order->status_id === OrderIssueService::STATUS_PENDING, 403);

        $snapshot = $order->template_snapshot ?? [];

        $this->editOrderId = $order->id;
        $this->presetCode = (string) ($snapshot['template_code'] ?? '');
        $this->fields = (array) ($snapshot['fields'] ?? []);
        // A provisional number is a placeholder, not the author's: the field stays empty.
        $this->orderNumber = OrderNumbering::display((string) $order->order_no);
        $this->orderDate = app(AzerbaijaniDateFormatter::class)->parse((string) ($snapshot['order_date_text'] ?? ''))?->format('Y-m-d') ?? '';
        $this->hasUploadedDocx = ! empty($snapshot['docx_path']);

        $this->pickPersonnel(empty($snapshot['personnel_id']) ? null : (int) $snapshot['personnel_id']);
        $this->pickHire(
            empty($snapshot['candidate_id']) ? null : (int) $snapshot['candidate_id'],
            $snapshot['hire_structure_id'] ?? null,
            $snapshot['hire_position_id'] ?? null,
        );
    }

    /**
     * The configured automatic number format ('' when numbers are typed by hand); with one,
     * the number field may be left empty and is filled in at approval.
     */
    public function autoNumberingFormat(): string
    {
        return app(OrderNumbering::class)->format();
    }

    public function isHire(): bool
    {
        return (bool) $this->template()?->isHire();
    }

    /**
     * The selected Word template, fetched once per request (isHire(), fieldDefs, etc.
     * all share this — no duplicate order_word_templates query).
     */
    private function template(): ?OrderWordTemplate
    {
        if (! $this->templateLoaded) {
            $this->templateCache = $this->presetCode === ''
                ? null
                : app(OrderTemplateProvider::class)->find($this->presetCode);
            $this->templateLoaded = true;
        }

        return $this->templateCache;
    }

    /**
     * @return array<string,string>
     */
    public function getPresetsProperty(OrderTemplateProvider $templates): array
    {
        return $templates->available();
    }

    /**
     * The manual fields the selected template declared — the only inputs the author
     * fills; automatic variables resolve from the employee/system context.
     *
     * @return array<int,array{key:string,label:string,type:string}>
     */
    public function getFieldDefsProperty(): array
    {
        return $this->template()?->manualFields() ?? [];
    }

    /**
     * The selected employee's vacation balance for the order's year, for the form to
     * display (entitled / used / remaining) — null unless this is a vacation order with
     * an employee chosen. Also carries the days requested on this order.
     *
     * @return array{year:int,total:int,used:int,remaining:int,requested:int}|null
     */
    public function getVacationBalanceProperty(OrderCompositionIssuer $issuer): ?array
    {
        $template = $this->template();

        return $template ? $issuer->vacationBalance($template, $this->composition(), persist: false) : null;
    }

    /**
     * Clear a field's "required" error the moment the author fills it in, and fill the
     * dates that follow from it (day count ↔ end date, end date → return-to-work date).
     */
    public function updatedFields($value, $key = null): void
    {
        // A single field updated (wire:model.live) → ($value, $key); the whole array
        // replaced → ($array). Clear the error for any key that now has a value.
        $pairs = is_array($value) ? $value : [$key => $value];
        foreach ($pairs as $k => $v) {
            if ($v !== null && ! is_array($v) && trim((string) $v) !== '') {
                $this->resetErrorBag('fields.'.$k);
            }
        }

        $template = $this->template();
        if ($template && is_string($key) && $key !== '') {
            $this->fields = app(OrderLeaveDateRules::class)->autofill($template, $this->fields, $key);
        }
    }

    public function updatedPresetCode(): void
    {
        $this->fields = [];
        $this->previewPdf = '';
        $this->previewHtml = '';
        $this->templateLoaded = false;
        $this->resetHireSubject();

        $this->applyFieldDefaults();
    }

    /** Fields with a usual value start with it (e.g. 126 days of maternity leave). */
    private function applyFieldDefaults(): void
    {
        foreach ($this->template()?->manualFields() ?? [] as $field) {
            if (filled($field['default']) && blank($this->fields[$field['key']] ?? null)) {
                $this->fields[$field['key']] = (string) $field['default'];
            }
        }
    }

    /**
     * Render the filled document and show it inline as a faithful PDF preview (via
     * LibreOffice). Nothing is persisted; the author checks it before issuing.
     */
    public function preview(OrderSubjectResolver $subjects, OrderDocumentBuilder $documents): void
    {
        $this->authorize('add-orders');
        $this->previewPdf = '';
        $this->previewHtml = '';

        $template = $this->templateOrError();
        if (! $template) {
            return;
        }
        $composition = $this->composition();
        if ($this->addErrors($subjects->subjectErrors($template, $composition))) {
            return;
        }

        $values = $documents->values($template, $composition);
        $pdf = $documents->renderPdf($template, $values);

        if ($pdf === null) {
            // No LibreOffice on this host — fall back to an HTML rendering of the same
            // document; only if that fails too, point the author to the Word download.
            $html = $documents->renderHtml($template, $values);
            if ($html === null) {
                $this->addError('previewPdf', __('orders::order_composer.errors.preview_unavailable'));

                return;
            }

            $this->previewHtml = $html;

            return;
        }

        $this->previewPdf = $pdf;
    }

    /**
     * Download the exact filled .docx without persisting an order.
     */
    public function downloadWord(OrderSubjectResolver $subjects, OrderDocumentBuilder $documents): ?BinaryFileResponse
    {
        $this->authorize('add-orders');

        $template = $this->templateOrError();
        if (! $template) {
            return null;
        }
        $composition = $this->composition();
        if ($this->addErrors($subjects->subjectErrors($template, $composition))) {
            return null;
        }

        $tmp = $documents->renderDocx($template, $documents->values($template, $composition));

        return response()->download($tmp, $composition->downloadName())->deleteFileAfterSend();
    }

    public function issue(): ?StreamedResponse
    {
        return $this->attemptIssue(autoVacancy: false);
    }

    /**
     * Confirm-modal entry point: create/expand the staff-schedule slot for the hire's
     * structure+position, then issue the order — all without leaving the page.
     */
    public function createVacancyAndIssue(): ?StreamedResponse
    {
        return $this->attemptIssue(autoVacancy: true);
    }

    private function attemptIssue(bool $autoVacancy): ?StreamedResponse
    {
        $this->authorize('add-orders');

        $this->validate(
            ['orderDate' => ['required', 'date_format:Y-m-d']],
            [],
            ['orderDate' => __('orders::order_composer.labels.date')],
        );

        $template = $this->templateOrError();
        if (! $template) {
            return null;
        }

        $composition = $this->composition();
        $outcome = app(OrderCompositionIssuer::class)->issue($template, $composition, $autoVacancy);
        $this->addErrors($outcome->errors);

        if ($outcome->isVacancyMissing()) {
            $this->dispatch('order-vacancy-missing', message: $outcome->message);

            return null;
        }

        if (! $outcome->isSaved()) {
            if ($outcome->message !== null) {
                $this->dispatch('orderError', $outcome->message);
            }

            return null;
        }

        $this->dispatch('orderAdded', $outcome->message);

        return $outcome->documentPath === null
            ? null
            : Storage::disk('local')->download($outcome->documentPath, $composition->downloadName());
    }

    /**
     * Replace this pending order's document with a user-corrected .docx, served
     * verbatim on every future print until the order is regenerated.
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
        $path = $this->uploadedDocx->storeAs('order-documents', $order->id.'-'.now()->timestamp.'.docx');

        $issuer->attachUploadedDocx($order, $path);

        $this->uploadedDocx = null;
        $this->hasUploadedDocx = true;

        $this->dispatch('orderAdded', __('orders::order_composer.messages.word_replaced'));
    }

    public function render(): View
    {
        return view('orders::livewire.orders.order-composer');
    }

    /**
     * Options for every list-bound (structure/position/rank/…) field type, keyed by
     * type, so the form can render the matching dropdown.
     *
     * @return array<string,array<int,array{id:int,label:string,depth:int}>>
     */
    public function getLookupOptionsProperty(OrderLookupFieldRegistry $lookups): array
    {
        $options = [];
        foreach ($lookups->types() as $type) {
            $options[$type['type']] = $lookups->options($type['type']);
        }

        return $options;
    }

    /**
     * The selected template, or null with an "unknown type" error on the form.
     */
    private function templateOrError(): ?OrderWordTemplate
    {
        $template = $this->template();
        if (! $template) {
            $this->addError('presetCode', __('orders::order_composer.errors.unknown_type'));
        }

        return $template;
    }

    /**
     * Put the given errors on the form; true when there were any.
     *
     * @param  array<string,string>  $errors
     */
    private function addErrors(array $errors): bool
    {
        foreach ($errors as $key => $message) {
            $this->addError($key, $message);
        }

        return $errors !== [];
    }

    /**
     * The date as printed on the order ("14.05.2026-cı il"); the input's raw value when
     * it cannot be read as a date.
     */
    private function documentDate(): string
    {
        $dates = app(AzerbaijaniDateFormatter::class);
        $date = $dates->parse($this->orderDate);

        return $date ? $dates->longDate($date) : $this->orderDate;
    }

    private function composition(): OrderComposition
    {
        return new OrderComposition(
            presetCode: $this->presetCode,
            personnelId: $this->personnelId,
            candidateId: $this->candidateId,
            hireStructureId: $this->hireStructureId,
            hirePositionId: $this->hirePositionId,
            fields: $this->fields,
            orderNumber: $this->orderNumber,
            orderDate: $this->documentDate(),
            organizationCity: $this->organizationCity,
            editOrderId: $this->editOrderId,
        );
    }
}
