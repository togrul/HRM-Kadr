<?php

namespace App\Livewire\Traits;

use App\Models\Candidate;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\Personnel;
use App\Traits\NormalizesDropdownPayloads;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;

trait TemplateCrud
{
    use DropdownConstructTrait;
    use NormalizesDropdownPayloads;
    use WithFileUploads;

    public $template_data = [
        'order_category_id' => null,
    ];

    public $searchCategory = '';

    public $title;

    public $templateModel;

    public function rules()
    {
        return [
            'template_data.id' => 'required|int|min:2|unique:orders,id'.(! empty($this->templateModel) ? ','.$this->templateModel : ''),
            'template_data.name' => 'required|string|min:2|max:150',
            // On a fresh upload the content is an UploadedFile and must be a
            // bounded .docx; on edit-without-reupload it is the stored string path.
            'template_data.content' => $this->contentIsFreshUpload()
                ? ['required', 'file', 'mimes:docx', 'max:10240']
                : ['required'],
            'template_data.order_category_id' => 'required|int|exists:order_categories,id',
            'template_data.order_model' => ['required', 'string', Rule::in($this->allowedOrderModels())],
            'template_data.blade' => ['required', 'string', Rule::in($this->allowedBladeValues())],
        ];
    }

    protected function contentIsFreshUpload(): bool
    {
        return data_get($this->template_data, 'content') instanceof UploadedFile;
    }

    /**
     * Persist the uploaded .docx under a sanitized, collision-free filename and
     * return its relative storage path. The raw template name is never used as a
     * filesystem path (prevents traversal / overwrite via crafted names).
     */
    protected function storeUploadedTemplate(): string
    {
        $safeName = Str::slug((string) ($this->template_data['name'] ?? 'template')) ?: 'template';
        $filename = $safeName.'-'.Str::random(8).'.docx';

        return $this->template_data['content']->storeAs('templates', $filename, 'public');
    }

    protected function validationAttributes()
    {
        return [
            'template_data.id' => __('orders::template_form.labels.id'),
            'template_data.name' => __('orders::template_form.labels.name'),
            'template_data.content' => __('orders::template_form.labels.content'),
            'template_data.order_category_id' => __('orders::template_form.labels.category'),
            'template_data.order_model' => __('orders::template_form.labels.model'),
            'template_data.blade' => __('orders::template_form.labels.page'),
        ];
    }

    public function mount()
    {
        if (! empty($this->templateModel)) {
            $this->fillTemplate();
            $this->title = __('orders::template_form.titles.edit_template');
        } else {
            $this->title = __('orders::template_form.titles.add_template');
            $this->template_data['order_model'] = $this->allowedOrderModels()[0] ?? '';
            $this->template_data['blade'] = $this->allowedBladeValues()[0] ?? Order::BLADE_DEFAULT;
        }
    }

    #[Computed]
    public function orderCategoryOptions(): array
    {
        $localeColumn = 'name_'.config('app.locale');

        $base = OrderCategory::query()
            ->select('id', DB::raw("{$localeColumn} as label"))
            ->orderBy($localeColumn);

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $localeColumn,
            searchTerm: $this->searchCategory,
            selectedId: data_get($this->template_data, 'order_category_id'),
            limit: 100
        );
    }

    public function templateModelOptions(): array
    {
        return collect($this->allowedOrderModels())
            ->map(fn (string $model) => [
                'value' => $model,
                'label' => class_basename($model),
            ])
            ->values()
            ->all();
    }

    public function bladeOptions(): array
    {
        return collect($this->allowedBladeValues())
            ->map(fn (string $blade) => [
                'value' => $blade,
                'label' => $blade,
            ])
            ->values()
            ->all();
    }

    private function allowedOrderModels(): array
    {
        $configured = collect(config('orders.template_master.order_models', [
            Personnel::class,
            Candidate::class,
        ]))
            ->filter(fn ($value) => is_string($value) && trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->values();

        $current = trim((string) data_get($this->template_data, 'order_model', ''));
        if ($current !== '' && ! $configured->contains($current)) {
            $configured->prepend($current);
        }

        return $configured->unique()->values()->all();
    }

    private function allowedBladeValues(): array
    {
        $configured = collect(config('orders.template_master.allowed_blades', [
            Order::BLADE_DEFAULT,
            Order::BLADE_VACATION,
            Order::BLADE_BUSINESS_TRIP,
        ]))
            ->filter(fn ($value) => is_string($value) && trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->values();

        return $configured->unique()->values()->all();
    }

    public function render()
    {
        $view_name = ! empty($this->templateModel)
            ? 'orders::livewire.orders.templates.edit-template'
            : 'orders::livewire.orders.templates.add-template';

        return view($view_name);
    }
}
