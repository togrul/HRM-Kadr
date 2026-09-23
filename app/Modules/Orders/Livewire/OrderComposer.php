<?php

namespace App\Modules\Orders\Livewire;

use App\Models\Candidate;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Modules\Orders\Application\Document\OrderComposition;
use App\Modules\Orders\Application\Document\OrderTemplateProvider;
use App\Modules\Orders\Infrastructure\Document\OrderCompositionIssuer;
use App\Modules\Orders\Infrastructure\Document\OrderDocumentBuilder;
use App\Modules\Orders\Infrastructure\Document\OrderDraftService;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Orders\Infrastructure\Document\OrderLookupFieldRegistry;
use App\Modules\Orders\Infrastructure\Document\OrderSubjectResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
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
    use AuthorizesRequests, WithFileUploads;

    public string $presetCode = '';

    public ?int $personnelId = null;

    public string $personnelQuery = '';

    public ?string $personnelLabel = null;

    // Hire orders pick a candidate (not an employee) and the structure/position they
    // are hired into; on approval the candidate is converted to an active employee.
    public ?int $candidateId = null;

    public string $candidateQuery = '';

    public ?string $candidateLabel = null;

    public ?int $hireStructureId = null;

    public ?int $hirePositionId = null;

    /** @var array<string,mixed> manual field key => value */
    public array $fields = [];

    public string $orderNumber = '';

    public string $orderDate = '';

    public string $organizationCity = OrderDraftService::ORGANIZATION_CITY;

    /** Set when editing an existing pending docx order (its order_logs id). */
    public ?int $editOrderId = null;

    /** A corrected .docx the user uploaded to replace the generated document. */
    public $uploadedDocx = null;

    public bool $hasUploadedDocx = false;

    /** Base64 of the generated PDF, shown inline as a faithful preview. */
    public string $previewPdf = '';

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
        abort_unless((string) $order->template_render_mode === OrderIssueService::RENDER_MODE_DOCX, 404);
        abort_unless((int) $order->status_id === OrderIssueService::STATUS_PENDING, 403);

        $snapshot = $order->template_snapshot ?? [];

        $this->editOrderId = $order->id;
        $this->presetCode = (string) ($snapshot['template_code'] ?? '');
        $this->fields = (array) ($snapshot['fields'] ?? []);
        $this->orderNumber = (string) $order->order_no;
        $this->orderDate = (string) ($snapshot['order_date_text'] ?? '');
        $this->hasUploadedDocx = ! empty($snapshot['docx_path']);

        $personnelId = $snapshot['personnel_id'] ?? null;
        if ($personnelId) {
            $this->personnelId = (int) $personnelId;
            $this->personnelLabel = optional(Personnel::find($this->personnelId))->fullname;
        }

        $this->hireStructureId = $snapshot['hire_structure_id'] ?? null;
        $this->hirePositionId = $snapshot['hire_position_id'] ?? null;
        $candidateId = $snapshot['candidate_id'] ?? null;
        if ($candidateId) {
            $this->candidateId = (int) $candidateId;
            $this->candidateLabel = optional(Candidate::find($this->candidateId))->fullname;
        }
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
     * Candidate picker for hire orders — only candidates in the "ready for order"
     * status (30) are offered.
     *
     * @return array<int,array{id:int,label:string}>
     */
    public function getCandidateResultsProperty(): array
    {
        $term = trim($this->candidateQuery);
        if (mb_strlen($term) < 2) {
            return [];
        }

        return Candidate::query()
            ->where('status_id', 30)
            ->where(fn ($q) => $q
                ->where('surname', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('patronymic', 'like', "%{$term}%"))
            ->orderBy('surname')
            ->limit(8)
            ->get(['id', 'surname', 'name', 'patronymic'])
            ->map(fn (Candidate $c) => [
                'id' => $c->id,
                'label' => trim("{$c->surname} {$c->name} {$c->patronymic}"),
            ])
            ->all();
    }

    public function selectCandidate(int $id): void
    {
        $candidate = Candidate::find($id);
        if ($candidate) {
            $this->candidateId = $candidate->id;
            $this->candidateLabel = $candidate->fullname;
            $this->hireStructureId ??= $candidate->structure_id;
        }
        $this->candidateQuery = '';
        $this->previewPdf = '';
    }

    public function clearCandidate(): void
    {
        $this->candidateId = null;
        $this->candidateLabel = null;
        $this->previewPdf = '';
    }

    /**
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
        $this->previewPdf = '';
    }

    public function clearPersonnel(): void
    {
        $this->personnelId = null;
        $this->personnelLabel = null;
        $this->previewPdf = '';
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

        return $template ? $issuer->vacationBalance($template, $this->composition()) : null;
    }

    /** Clear a field's "required" error the moment the author fills it in. */
    public function updatedFields($value, $key = null): void
    {
        // A single field updated (wire:model.live) → ($value, $key); the whole array
        // replaced → ($array). Clear the error for any key that now has a value.
        $pairs = is_array($value) ? $value : [$key => $value];
        foreach ($pairs as $k => $v) {
            if ($k !== null && $v !== null && ! is_array($v) && trim((string) $v) !== '') {
                $this->resetErrorBag('fields.'.$k);
            }
        }
    }

    public function updatedPresetCode(): void
    {
        $this->fields = [];
        $this->previewPdf = '';
        $this->templateLoaded = false;
        // Switching types clears any subject picked for the previous one.
        $this->candidateId = null;
        $this->candidateLabel = null;
        $this->hireStructureId = null;
        $this->hirePositionId = null;
    }

    /**
     * Render the filled document and show it inline as a faithful PDF preview (via
     * LibreOffice). Nothing is persisted; the author checks it before issuing.
     */
    public function preview(OrderSubjectResolver $subjects, OrderDocumentBuilder $documents): void
    {
        $this->authorize('add-orders');
        $this->previewPdf = '';

        $template = $this->templateOrError();
        if (! $template) {
            return;
        }
        $composition = $this->composition();
        if ($this->addErrors($subjects->subjectErrors($template, $composition))) {
            return;
        }

        $pdf = $documents->renderPdf($template, $documents->values($template, $composition));

        if ($pdf === null) {
            // No LibreOffice on this host — point the author to the exact Word download.
            $this->addError('previewPdf', __('orders::order_composer.errors.preview_unavailable'));

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
     * @return array<string,array<int,string>>
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
            orderDate: $this->orderDate,
            organizationCity: $this->organizationCity,
            editOrderId: $this->editOrderId,
        );
    }
}
