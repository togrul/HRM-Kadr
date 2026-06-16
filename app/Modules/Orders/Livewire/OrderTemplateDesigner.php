<?php

namespace App\Modules\Orders\Livewire;

use App\Models\Personnel;
use App\Services\Orders\Document\DesignerBlockCodec;
use App\Services\Orders\Document\OrderRenderService;
use App\Services\Orders\Document\OrderTemplateProvider;
use App\Services\Orders\Document\OrderTemplateRepository;
use App\Services\Orders\Document\TemplateBlock;
use App\Services\Orders\Document\TemplateFieldSchema;
use App\Services\Orders\Variables\OrderVariableRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * The authoring designer: lets an HR user (no code) build or edit an order type —
 * arrange text blocks with {{ variable }} placeholders from a palette, preview with
 * sample data, and save the template to the database for the composer to use.
 */
class OrderTemplateDesigner extends Component
{
    use AuthorizesRequests;

    public string $code = '';

    public string $label = '';

    public bool $isNew = true;

    /** @var array<int,array<string,mixed>> */
    public array $rows = [];

    public string $previewHtml = '';

    protected function rules(): array
    {
        return [
            'code' => 'required|regex:/^[a-z0-9_]+$/|max:64',
            'label' => 'required|string|max:120',
            'rows' => 'required|array|min:1',
            'rows.*.kind' => 'required|string',
        ];
    }

    public function mount(?string $code = null): void
    {
        $this->authorize('edit-orders');

        if ($code) {
            $this->code = $code;
            $this->isNew = false;
            $this->label = app(OrderTemplateProvider::class)->available()[$code] ?? $code;
            $this->rows = app(DesignerBlockCodec::class)->toEditable(
                app(OrderTemplateProvider::class)->blocks($code)
            );
        } else {
            $this->rows = [app(DesignerBlockCodec::class)->blankRow(TemplateBlock::HEADING)];
        }
    }

    /**
     * @return array<string,array<int,array<string,string>>>
     */
    public function getVariableGroupsProperty(OrderVariableRegistry $registry): array
    {
        return $registry->grouped();
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getBlockKindsProperty(): array
    {
        return [
            ['kind' => TemplateBlock::HEADING, 'label' => 'Başlıq (mərkəz)'],
            ['kind' => TemplateBlock::PARAGRAPH, 'label' => 'Paraqraf'],
            ['kind' => TemplateBlock::CLAUSES, 'label' => 'Bəndlər (sətir-sətir)'],
            ['kind' => TemplateBlock::SPLIT, 'label' => 'İki sütun (sol/sağ)'],
            ['kind' => TemplateBlock::SIGNATURE, 'label' => 'İmza bloku'],
            ['kind' => TemplateBlock::SPACER, 'label' => 'Boşluq'],
        ];
    }

    public function addBlock(string $kind = TemplateBlock::PARAGRAPH): void
    {
        $this->rows[] = app(DesignerBlockCodec::class)->blankRow($kind);
    }

    public function removeBlock(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        $this->previewHtml = '';
    }

    public function moveBlock(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (isset($this->rows[$index], $this->rows[$target])) {
            [$this->rows[$index], $this->rows[$target]] = [$this->rows[$target], $this->rows[$index]];
            $this->rows = array_values($this->rows);
        }
    }

    public function preview(DesignerBlockCodec $codec, OrderRenderService $renderer, TemplateFieldSchema $schema): void
    {
        $blocks = $codec->toBlocks($this->rows);

        // Sample data so the author sees a realistic, fully-resolved document.
        $fields = [];
        foreach ($schema->for($blocks) as $field) {
            $fields[$field['key']] = '['.$field['label'].']';
        }

        $this->previewHtml = $renderer->previewBlocks($blocks, [
            'personnel' => Personnel::with(['structure:id,name', 'position:id,name'])
                ->whereNotNull('structure_id')->whereNotNull('position_id')->first(),
            'fields' => $fields,
            'order_number' => '000-X',
            'order_date' => '01 yanvar 2026-cı il',
            'system' => ['organization_city' => 'Bakı şəhəri'],
        ]);
    }

    public function save(DesignerBlockCodec $codec, OrderTemplateRepository $repository)
    {
        $this->authorize('edit-orders');
        $this->code = Str::of($this->code)->lower()->replace(' ', '_')->value();
        $this->validate();

        $repository->save($this->code, $this->label, $codec->toBlocks($this->rows), auth()->id());
        $this->isNew = false;

        $this->dispatch('templateSaved', __('orders::order_composer.messages.template_saved'));
    }

    public function render()
    {
        return view('orders::livewire.orders.order-template-designer');
    }
}
