<?php

namespace App\Modules\Orders\Livewire;

use App\Models\Candidate;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Services\Orders\Document\DocxTemplateRenderer;
use App\Services\Orders\Document\DocxToPdfConverter;
use App\Services\Orders\Document\DocxVariableResolver;
use App\Services\Orders\Document\OrderLookupFieldRegistry;
use App\Services\Orders\Document\OrderIssueService;
use App\Services\Orders\Document\OrderTemplateProvider;
use App\Services\Chief\ChiefResolver;
use App\Support\Language\AzerbaijaniDateFormatter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

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

    public string $organizationCity = 'Bakı şəhəri';

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

    /** Per-request cache of the resolved signatory snapshot (private → not persisted). */
    private ?array $signatoryCache = null;

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
    public function getVacationBalanceProperty(): ?array
    {
        $template = $this->template();

        return ($template && $template->effect === 'vacation') ? $this->vacationBalanceFor($template) : null;
    }

    /**
     * @return array{year:int,total:int,used:int,remaining:int,requested:int}|null
     */
    private function vacationBalanceFor(OrderWordTemplate $template): ?array
    {
        $personnel = $this->personnel();
        if (! $personnel) {
            return null;
        }

        // Only day-counted paid leave has a balance to check/deduct. Date-range leaves
        // without a day count (e.g. unpaid leave) are not gated and show no balance.
        $hasDaysRole = collect($template->variables ?? [])
            ->contains(fn ($v) => ($v['effect_role'] ?? null) === 'days');
        if (! $hasDaysRole) {
            return null;
        }

        $start = app(\App\Support\Language\AzerbaijaniDateFormatter::class)->parse($this->effectFieldValue($template, 'start_date'));
        $year = (int) ($start?->year ?? now()->year);

        $snapshot = app(\App\Services\Vacation\VacationBalanceService::class)->snapshot($personnel, $year);
        $snapshot['year'] = $year;
        $snapshot['requested'] = (int) ($this->effectFieldValue($template, 'days') ?? 0);

        return $snapshot;
    }

    /** The value the author entered for the template variable carrying $role. */
    private function effectFieldValue(OrderWordTemplate $template, string $role): ?string
    {
        foreach ($template->variables ?? [] as $variable) {
            if (($variable['effect_role'] ?? null) === $role && ! empty($variable['token'])) {
                $value = $this->fields[$variable['token']] ?? null;

                return $value === null ? null : (string) $value;
            }
        }

        return null;
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
    public function preview(OrderTemplateProvider $templates, DocxVariableResolver $resolver, DocxTemplateRenderer $renderer, DocxToPdfConverter $pdf): void
    {
        $this->authorize('add-orders');
        $this->previewPdf = '';

        $template = $this->template();
        if (! $template) {
            $this->addError('presetCode', __('orders::order_composer.errors.unknown_type'));

            return;
        }
        if (! $this->ensureSubject($template)) {
            return;
        }

        $values = $resolver->resolve($template, $this->subject($template), $this->fields, $this->systemContext());
        $tmp = $renderer->renderToFile($template->docx_path, $values);

        $pdfPath = $pdf->convert($tmp);
        @unlink($tmp);

        if ($pdfPath === null) {
            // No LibreOffice on this host — point the author to the exact Word download.
            $this->addError('previewPdf', __('orders::order_composer.errors.preview_unavailable'));

            return;
        }

        $this->previewPdf = base64_encode((string) file_get_contents($pdfPath));
        @unlink($pdfPath);
    }

    /**
     * Download the exact filled .docx without persisting an order.
     */
    public function downloadWord(OrderTemplateProvider $templates, DocxVariableResolver $resolver, DocxTemplateRenderer $renderer)
    {
        $this->authorize('add-orders');

        $template = $this->template();
        if (! $template) {
            $this->addError('presetCode', __('orders::order_composer.errors.unknown_type'));

            return null;
        }
        if (! $this->ensureSubject($template)) {
            return null;
        }

        $values = $resolver->resolve($template, $this->subject($template), $this->fields, $this->systemContext());
        $tmp = $renderer->renderToFile($template->docx_path, $values);

        return response()->download($tmp, $this->downloadName())->deleteFileAfterSend();
    }

    public function issue()
    {
        return $this->attemptIssue(autoVacancy: false);
    }

    /**
     * Confirm-modal entry point: create/expand the staff-schedule slot for the hire's
     * structure+position, then issue the order — all without leaving the page.
     */
    public function createVacancyAndIssue()
    {
        return $this->attemptIssue(autoVacancy: true);
    }

    private function attemptIssue(bool $autoVacancy)
    {
        $this->authorize('add-orders');

        $issuer = app(OrderIssueService::class);
        $resolver = app(DocxVariableResolver::class);
        $renderer = app(DocxTemplateRenderer::class);

        $template = $this->template();
        if (! $template) {
            $this->addError('presetCode', __('orders::order_composer.errors.unknown_type'));

            return null;
        }
        if (trim($this->orderNumber) === '') {
            $this->addError('orderNumber', __('orders::order_composer.errors.number_required'));

            return null;
        }
        if (! $this->ensureSubject($template)) {
            return null;
        }

        // Every declared manual field must be filled — an order document must not be
        // saved with blank slots (e.g. a leave with no start/end dates). Errors are shown
        // both on the inputs (addError) and as a summary toast.
        $missing = [];
        foreach ($template->manualFields() as $field) {
            $value = $this->fields[$field['key']] ?? null;
            if ($value === null || trim((string) $value) === '') {
                $this->addError('fields.'.$field['key'], __('orders::order_composer.errors.field_required'));
                $missing[] = $field['label'];
            }
        }
        if ($missing !== []) {
            $this->dispatch('orderError', __('orders::order_composer.errors.fields_required', [
                'fields' => implode(', ', $missing),
            ]));

            return null;
        }

        // Vacation gate: at least one day, and never more than the employee's remaining
        // annual balance. Block with a clear notification otherwise.
        if ($template->effect === 'vacation' && $this->personnelId) {
            $balance = $this->vacationBalanceFor($template);
            if ($balance && $balance['requested'] < 1) {
                $this->dispatch('orderError', __('orders::order_composer.vacation.min_days'));

                return null;
            }
            if ($balance && $balance['requested'] > $balance['remaining']) {
                $this->dispatch('orderError', __('orders::order_composer.vacation.exceeded', [
                    'year' => $balance['year'],
                    'total' => $balance['total'],
                    'used' => $balance['used'],
                    'remaining' => $balance['remaining'],
                    'requested' => $balance['requested'],
                ]));

                return null;
            }
        }

        // Staff-schedule (ştat cədvəli) vacancy gate for new hire orders: there must be
        // a free slot for the chosen structure+position. If not, prompt the author to
        // auto-create one (handled by createVacancyAndIssue → autoVacancy).
        if ($template->isHire() && ! $this->isEditing()) {
            $vacancy = app(\App\Services\Staff\StaffScheduleVacancyService::class);

            if ($autoVacancy) {
                $vacancy->ensureOneVacancy((int) $this->hireStructureId, (int) $this->hirePositionId);
            } elseif ($vacancy->vacancy($this->hireStructureId, $this->hirePositionId) <= 0) {
                $this->dispatch('order-vacancy-missing', message: __('orders::order_composer.vacancy.confirm', [
                    'structure' => Structure::find($this->hireStructureId)?->name ?? '—',
                    'position' => Position::find($this->hirePositionId)?->name ?? '—',
                ]));

                return null;
            }
        }

        $payload = [
            'template_code' => $this->presetCode,
            'label' => $template->label,
            'personnel_id' => $template->isHire() ? null : $this->personnelId,
            'candidate_id' => $template->isHire() ? $this->candidateId : null,
            'hire_structure_id' => $template->isHire() ? $this->hireStructureId : null,
            'hire_position_id' => $template->isHire() ? $this->hirePositionId : null,
            'fields' => $this->fields,
            'order_number' => trim($this->orderNumber),
            'order_date' => $this->orderDate,
            // Freeze who signed (permanent chief or active delegate) as-of the order date,
            // so historical orders keep naming whoever was acting then.
            'signatory' => $this->signatorySnapshot(),
        ];

        $values = $resolver->resolve($template, $this->subject($template), $this->fields, $this->systemContext());

        if ($this->isEditing()) {
            $order = OrderLog::findOrFail($this->editOrderId);
            $issuer->updateWord($order, $payload);
            $this->renderAndStore($order, $template->docx_path, $values, $issuer, $renderer);

            $this->dispatch('orderAdded', __('orders::order_composer.messages.order_updated'));

            return null;
        }

        $order = $issuer->issueWord($payload);
        $stored = $this->renderAndStore($order, $template->docx_path, $values, $issuer, $renderer);

        $this->dispatch('orderAdded', __('orders::order_composer.messages.order_issued'));

        return Storage::disk('local')->download($stored, $this->downloadName());
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

    public function render()
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
     * Render the order's filled .docx and store it as the order's authoritative
     * document (served on print).
     *
     * @param  array<string,string>  $values
     */
    private function renderAndStore(OrderLog $order, string $masterPath, array $values, OrderIssueService $issuer, DocxTemplateRenderer $renderer): string
    {
        $tmp = $renderer->renderToFile($masterPath, $values);

        $stored = 'order-documents/'.$order->id.'.docx';
        Storage::disk('local')->put($stored, (string) file_get_contents($tmp));
        @unlink($tmp);

        $issuer->attachUploadedDocx($order, $stored);

        return $stored;
    }

    /**
     * The person the document's employee.* variables resolve from: the picked employee,
     * or (for hire) a transient employee built from the candidate + the structure/
     * position they are hired into — so names/structure/position decline correctly.
     */
    private function subject(OrderWordTemplate $template): ?Personnel
    {
        if (! $template->isHire()) {
            return $this->personnel();
        }

        $candidate = $this->candidateId ? Candidate::find($this->candidateId) : null;
        if (! $candidate) {
            return null;
        }

        $pseudo = new Personnel;
        $pseudo->surname = $candidate->surname;
        $pseudo->name = $candidate->name;
        $pseudo->patronymic = $candidate->patronymic;
        $pseudo->gender = $candidate->gender;
        $pseudo->structure_id = $this->hireStructureId;
        $pseudo->setRelation('structure', $this->hireStructureId ? Structure::find($this->hireStructureId) : null);
        $pseudo->setRelation('position', $this->hirePositionId ? Position::find($this->hirePositionId) : null);

        return $pseudo;
    }

    private function personnel(): ?Personnel
    {
        return $this->personnelId
            ? Personnel::with(['structure:id,name', 'position:id,name'])->find($this->personnelId)
            : null;
    }

    /**
     * Validate the order's subject: a hire needs a candidate + target structure/position;
     * other types need an employee only when the template maps an employee.* variable.
     */
    private function ensureSubject(OrderWordTemplate $template): bool
    {
        if ($template->isHire()) {
            if (! $this->candidateId) {
                $this->addError('candidateId', __('orders::order_composer.errors.candidate_required'));

                return false;
            }
            if (! $this->hireStructureId || ! $this->hirePositionId) {
                $this->addError('hirePositionId', __('orders::order_composer.errors.hire_target_required'));

                return false;
            }

            return true;
        }

        $needsEmployee = collect($template->variables ?? [])
            ->contains(fn ($v) => ($v['source'] ?? '') === 'auto' && str_starts_with((string) ($v['auto_key'] ?? ''), 'employee.'));

        if ($needsEmployee && ! $this->personnelId) {
            $this->addError('personnelId', __('orders::order_composer.errors.personnel_required'));

            return false;
        }

        return true;
    }

    /**
     * The system.* context for this order, keyed by the registry's variable keys.
     *
     * @return array<string,string>
     */
    private function systemContext(): array
    {
        $signatory = $this->signatorySnapshot();

        return [
            'system.order_number' => $this->orderNumber,
            'system.order_date' => $this->orderDate,
            'system.organization_city' => $this->organizationCity,
            'system.signatory_full_name' => (string) ($signatory['fullname'] ?? ''),
            'system.signatory_title' => (string) ($signatory['title'] ?? ''),
        ];
    }

    /**
     * Who signs this order — the permanent chief, or the active temporary delegate
     * (müvəqqəti həvalə) on the order's date. Resolved as-of the order date (not "now")
     * so historical orders name whoever was acting then; cached for the request so the
     * system context and the persisted snapshot agree. Falls back to today when the
     * author's free-text date can't be parsed.
     *
     * @return array<string,mixed>
     */
    private function signatorySnapshot(): array
    {
        if ($this->signatoryCache === null) {
            $date = app(AzerbaijaniDateFormatter::class)->parse($this->orderDate) ?? now();
            $this->signatoryCache = app(ChiefResolver::class)->current($date);
        }

        return $this->signatoryCache;
    }

    private function downloadName(): string
    {
        $code = $this->presetCode !== '' ? $this->presetCode : 'order';
        $number = $this->orderNumber !== '' ? '_'.$this->orderNumber : '';

        return str_replace(['/', '\\'], '-', $code.$number).'.docx';
    }
}
