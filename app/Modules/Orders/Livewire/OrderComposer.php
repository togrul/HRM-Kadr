<?php

namespace App\Modules\Orders\Livewire;

use App\Models\Personnel;
use App\Services\Orders\Document\OrderFieldTransformer;
use App\Services\Orders\Document\OrderRenderService;
use App\Services\Orders\Document\OrderTemplateProvider;
use App\Services\Orders\Document\TemplateFieldSchema;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

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
    use AuthorizesRequests;

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

    public function mount(?string $presetCode = null, ?int $personnelId = null): void
    {
        $this->authorize('add-orders');
        // Nullable + coalesced: as a full-page route component Livewire may pass null
        // for params not present in the route ({personnelId?}), so guard the defaults.
        $this->presetCode = $presetCode ?? '';
        $this->personnelId = $personnelId;

        if ($personnelId) {
            $this->personnelLabel = optional(Personnel::find($personnelId))->fullname;
        }
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

    public function download(OrderRenderService $renderer)
    {
        $this->authorize('add-orders');

        $html = $this->editedHtml !== '' ? $this->editedHtml : $this->previewHtml;
        if ($html === '') {
            $this->addError('previewHtml', __('orders::order_composer.errors.nothing_to_generate'));

            return null;
        }

        $snapshot = $renderer->finalize($html);

        return response()->download($snapshot->docxPath, $this->downloadName())->deleteFileAfterSend();
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
        return [
            'personnel' => $this->personnelId
                ? Personnel::with(['structure:id,name', 'position:id,name'])->find($this->personnelId)
                : null,
            'fields' => app(OrderFieldTransformer::class)->transform($this->fields),
            'order_number' => $this->orderNumber,
            'order_date' => $this->orderDate,
            'system' => ['organization_city' => $this->organizationCity],
        ];
    }

    private function downloadName(): string
    {
        $code = $this->presetCode !== '' ? $this->presetCode : 'order';
        $number = $this->orderNumber !== '' ? '_'.$this->orderNumber : '';

        return $code.$number.'.docx';
    }
}
