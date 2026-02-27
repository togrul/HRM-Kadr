<?php

namespace App\Livewire\Traits;

use App\Models\Candidate;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\Personnel;
use App\Traits\NormalizesDropdownPayloads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
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
            'template_data.name' => 'required|string|min:2',
            'template_data.content' => 'required',
            'template_data.order_category_id' => 'required|int|exists:order_categories,id',
            'template_data.order_model' => ['required', 'string', Rule::in($this->allowedOrderModels())],
            'template_data.blade' => ['required', 'string', Rule::in($this->allowedBladeValues())],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'template_data.id' => __('Id'),
            'template_data.name' => __('Name'),
            'template_data.content' => __('Content'),
            'template_data.order_category_id' => __('Category'),
            'template_data.order_model' => __('Model'),
            'template_data.blade' => __('Page'),
        ];
    }

    public function mount()
    {
        if (! empty($this->templateModel)) {
            $this->fillTemplate();
            $this->title = __('Edit template');
        } else {
            $this->title = __('Add template');
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
