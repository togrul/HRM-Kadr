<?php

namespace App\Modules\Orders\Livewire;

use App\Services\Orders\Document\DocxPlaceholderParser;
use App\Services\Orders\Document\DocxTemplateRenderer;
use App\Services\Orders\Document\DocxToPdfConverter;
use App\Services\Orders\Document\OrderLookupFieldRegistry;
use App\Services\Orders\Document\OrderWordTemplateRepository;
use App\Services\Orders\Variables\OrderVariableRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Word-upload order-type designer: the HR author prepares the whole order in MS Word,
 * marks dynamic parts with [bracket] placeholders, and uploads the .docx here. We
 * parse the file, list every placeholder in the "Dəyişənlər" panel, and let the author
 * map each one to an automatic data source (employee/system, declension-aware) or a
 * manual per-order field. On save the .docx is normalized to a ${token} master the
 * composer fills.
 */
class OrderTemplateDesigner extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public string $code = '';

    public string $label = '';

    public bool $isNew = true;

    /** The HR action this order type performs on approval (none|vacation|transfer…). */
    public string $effect = 'none';

    /** A freshly selected .docx (Livewire temporary upload). */
    public $upload = null;

    public string $originalFileName = '';

    /** Relative path of the stored master on the 'local' disk (set after save/edit). */
    public ?string $docxPath = null;

    /**
     * The editable mapping rows, one per detected placeholder.
     *
     * @var array<int,array{token:string,label:string,source:string,auto_key:string,field_type:string}>
     */
    public array $variables = [];

    /** Base64 of the stored template rendered with [labels], shown as a PDF preview. */
    public string $templatePdf = '';

    public function mount(?string $code = null): void
    {
        $this->authorize('edit-orders');

        if ($code === null || $code === '') {
            return;
        }

        $template = app(OrderWordTemplateRepository::class)->find($code);
        if (! $template) {
            return;
        }

        $this->code = $template->code;
        $this->label = $template->label;
        $this->effect = $template->effect ?? 'none';
        $this->docxPath = $template->docx_path;
        $this->originalFileName = $code.'.docx';
        $this->isNew = false;
        $this->variables = $this->toEditable($template->variables ?? []);
    }

    /**
     * Effect options for the order-type selector + the roles of the current effect (for
     * the per-variable role dropdown).
     */
    public function getEffectOptionsProperty(\App\Services\Orders\Document\Effects\OrderEffectCatalog $catalog): array
    {
        return $catalog->options();
    }

    /**
     * @return array<int,array{key:string,label:string,type:string}>
     */
    public function getEffectRolesProperty(\App\Services\Orders\Document\Effects\OrderEffectCatalog $catalog): array
    {
        return $catalog->roles($this->effect);
    }

    /** Changing the effect clears any roles that no longer belong to it. */
    public function updatedEffect(): void
    {
        $valid = array_column($this->effectRoles, 'key');
        foreach ($this->variables as $i => $variable) {
            if (! in_array($variable['effect_role'] ?? '', $valid, true)) {
                $this->variables[$i]['effect_role'] = '';
            }
        }
    }

    protected function rules(): array
    {
        return [
            'code' => 'required|regex:/^[a-z0-9_]+$/|max:64',
            'label' => 'required|string|max:120',
        ];
    }

    /**
     * @return array<string,array<int,array{key:string,label:string,group:string,sample:string}>>
     */
    public function getVariableGroupsProperty(OrderVariableRegistry $registry): array
    {
        return $registry->grouped();
    }

    /**
     * Existing saved Word templates the author can open to view or correct.
     *
     * @return array<string,string> code => label
     */
    public function getTemplatesProperty(OrderWordTemplateRepository $repository): array
    {
        return $repository->available();
    }

    /**
     * Manual field input types the author can pick for a "filled per order" variable:
     * the plain inputs plus every project list it can be bound to (structure, position,
     * rank, …) from the lookup registry.
     *
     * @return array<int,array{type:string,label:string}>
     */
    public function getFieldTypesProperty(OrderLookupFieldRegistry $lookups): array
    {
        return array_merge([
            ['type' => 'text', 'label' => 'Mətn'],
            ['type' => 'number', 'label' => 'Rəqəm'],
            ['type' => 'number_words', 'label' => 'Rəqəm (sözlə)'],
            ['type' => 'date', 'label' => 'Tarix'],
            ['type' => 'work_year', 'label' => 'İş ili (tarixdən aralıq)'],
        ], $lookups->types());
    }

    /**
     * Parse the uploaded .docx the moment it is selected and (re)build the variable list.
     */
    public function updatedUpload(): void
    {
        // Lifecycle hooks don't get container injection — resolve the registry directly.
        $registry = app(OrderVariableRegistry::class);

        $this->validate([
            'upload' => ['required', 'file', 'mimes:docx,doc', 'max:10240'],
        ], [], ['upload' => __('orders::order_composer.designer.word_file')]);

        $this->templatePdf = '';
        $this->originalFileName = $this->upload->getClientOriginalName();

        $labels = app(DocxPlaceholderParser::class)->extract($this->upload->getRealPath());

        $this->variables = [];
        foreach ($labels as $i => $label) {
            $suggestion = $this->suggestAutoKey($label, $registry);
            $this->variables[] = [
                'token' => 'var_'.($i + 1),
                'label' => $label,
                'source' => $suggestion !== null ? 'auto' : 'manual',
                'auto_key' => $suggestion ?? '',
                'field_type' => 'text',
                'effect_role' => '',
            ];
        }
    }

    public function save(DocxPlaceholderParser $parser, OrderWordTemplateRepository $repository, OrderVariableRegistry $registry)
    {
        $this->authorize('edit-orders');

        $this->code = Str::of($this->code)->lower()->replace(' ', '_')->value();
        $this->validate();

        if ($this->isNew && $this->upload === null) {
            $this->addError('upload', __('orders::order_composer.designer.word_required'));

            return;
        }

        if ($this->variables === []) {
            $this->addError('upload', __('orders::order_composer.designer.no_variables'));

            return;
        }

        // Every placeholder must be mapped to a resolvable source.
        foreach ($this->variables as $i => $variable) {
            if ($variable['source'] === 'auto' && ! $registry->isResolvable($variable['auto_key'])) {
                $this->addError("variables.$i.auto_key", __('orders::order_composer.designer.choose_source'));

                return;
            }
        }

        // Only (re)normalize the master when a new file was uploaded; otherwise the
        // existing master already carries the ${token} markers and just the mapping changed.
        if ($this->upload !== null) {
            // Re-uploading replaces the master — archive the current one as a version first.
            if (! $this->isNew) {
                $existing = $repository->find($this->code);
                if ($existing) {
                    app(\App\Services\Orders\Document\OrderWordTemplateVersioner::class)->archive($existing);
                }
            }

            $relative = 'order-templates/'.$this->code.'.docx';
            $destination = Storage::disk('local')->path($relative);
            File::ensureDirectoryExists(dirname($destination));

            $parser->normalize($this->upload->getRealPath(), $this->labelToToken(), $destination);
            $this->docxPath = $relative;
        }

        $repository->save($this->code, $this->label, $this->effect, (string) $this->docxPath, $this->toStorage(), auth()->id());

        $this->isNew = false;
        $this->upload = null;

        $this->dispatch('templateAdded', __('orders::order_composer.messages.template_saved'));
    }

    /**
     * Show the stored template as a PDF with each placeholder displayed as its [label],
     * so the author can check the layout and that every variable sits in the right spot.
     */
    public function previewTemplate(DocxTemplateRenderer $renderer, DocxToPdfConverter $pdf): void
    {
        $this->authorize('edit-orders');
        $this->templatePdf = '';

        if ($this->docxPath === null) {
            return;
        }

        $tmp = $renderer->renderToFile($this->docxPath, $this->tokenLabels());
        $pdfPath = $pdf->convert($tmp);
        @unlink($tmp);

        if ($pdfPath === null) {
            $this->addError('templatePdf', __('orders::order_composer.errors.preview_unavailable'));

            return;
        }

        $this->templatePdf = base64_encode((string) file_get_contents($pdfPath));
        @unlink($pdfPath);
    }

    /**
     * Past versions of this template (archived on each re-upload).
     *
     * @return \Illuminate\Support\Collection<int,\App\Models\OrderWordTemplateVersion>
     */
    public function getVersionsProperty(OrderWordTemplateRepository $repository)
    {
        if ($this->isNew || $this->code === '') {
            return collect();
        }

        return optional($repository->find($this->code))?->versions()->get() ?? collect();
    }

    /**
     * Download an archived version as a Word file with [labels] in place.
     */
    public function downloadVersion(int $id, DocxTemplateRenderer $renderer)
    {
        $this->authorize('edit-orders');

        $version = \App\Models\OrderWordTemplateVersion::find($id);
        if (! $version) {
            return null;
        }

        $labels = [];
        foreach ($version->variables ?? [] as $v) {
            if (! empty($v['token'])) {
                $labels[$v['token']] = '['.($v['label'] ?? '').']';
            }
        }

        $tmp = $renderer->renderToFile($version->docx_path, $labels);

        return response()->download($tmp, $this->code.'-v'.$version->version.'.docx')->deleteFileAfterSend();
    }

    /**
     * Download the template as a Word file with [labels] in place, so the author can
     * correct the wording/formatting in MS Word and re-upload it.
     */
    public function downloadTemplate(DocxTemplateRenderer $renderer)
    {
        $this->authorize('edit-orders');

        if ($this->docxPath === null) {
            return null;
        }

        $tmp = $renderer->renderToFile($this->docxPath, $this->tokenLabels());

        return response()->download($tmp, ($this->code !== '' ? $this->code : 'sablon').'.docx')->deleteFileAfterSend();
    }

    public function render()
    {
        return view('orders::livewire.orders.order-template-designer');
    }

    /**
     * @return array<string,string> raw label => bare token
     */
    private function labelToToken(): array
    {
        $map = [];
        foreach ($this->variables as $variable) {
            $map[$variable['label']] = $variable['token'];
        }

        return $map;
    }

    /**
     * token => "[label]" — fills a stored ${token} master so it reads like the
     * bracketed template the author prepared.
     *
     * @return array<string,string>
     */
    private function tokenLabels(): array
    {
        $map = [];
        foreach ($this->variables as $variable) {
            $map[$variable['token']] = '['.$variable['label'].']';
        }

        return $map;
    }

    /**
     * Editable rows → stored mapping shape.
     *
     * @return array<int,array{token:string,label:string,source:string,auto_key:?string,field:?array{key:string,type:string}}>
     */
    private function toStorage(): array
    {
        return array_map(function (array $v) {
            $auto = $v['source'] === 'auto';

            return [
                'token' => $v['token'],
                'label' => $v['label'],
                'source' => $auto ? 'auto' : 'manual',
                'auto_key' => $auto ? $v['auto_key'] : null,
                'field' => $auto ? null : ['key' => $v['token'], 'type' => $v['field_type']],
                // Which structured input this variable feeds the approval effect (manual only).
                'effect_role' => (! $auto && ($v['effect_role'] ?? '') !== '') ? $v['effect_role'] : null,
            ];
        }, $this->variables);
    }

    /**
     * Stored mapping shape → editable rows.
     *
     * @param  array<int,array<string,mixed>>  $stored
     * @return array<int,array{token:string,label:string,source:string,auto_key:string,field_type:string}>
     */
    private function toEditable(array $stored): array
    {
        return array_map(fn (array $v) => [
            'token' => (string) ($v['token'] ?? ''),
            'label' => (string) ($v['label'] ?? ''),
            'source' => (string) ($v['source'] ?? 'manual'),
            'auto_key' => (string) ($v['auto_key'] ?? ''),
            'field_type' => (string) ($v['field']['type'] ?? 'text'),
            'effect_role' => (string) ($v['effect_role'] ?? ''),
        ], $stored);
    }

    /**
     * Best-effort match of a bracket label to a catalog variable key, so common
     * placeholders ("Tam ad") are pre-mapped. Returns null when nothing fits.
     */
    private function suggestAutoKey(string $label, OrderVariableRegistry $registry): ?string
    {
        $needle = $this->fold($label);
        if ($needle === '') {
            return null;
        }

        $best = null;
        foreach ($registry->all() as $variable) {
            $hay = $this->fold($variable['label']);
            if ($hay === $needle) {
                return $variable['key'];
            }
            if ($best === null && ($hay !== '' && (str_contains($hay, $needle) || str_contains($needle, $hay)))) {
                $best = $variable['key'];
            }
        }

        return $best;
    }

    private function fold(string $value): string
    {
        return trim(mb_strtolower($value, 'UTF-8'));
    }
}
