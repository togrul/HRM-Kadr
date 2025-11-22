<?php

namespace App\Livewire\Traits;

use App\Models\OrderCategory;
use App\Traits\NormalizesDropdownPayloads;
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
            'template_data.order_model' => 'required|string',
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
        ];
    }

    public function mount()
    {
        if (! empty($this->templateModel)) {
            $this->fillTemplate();
            $this->title = __('Edit template');
        } else {
            $this->title = __('Add template');
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

    public function render()
    {
        $view_name = ! empty($this->templateModel)
            ? 'orders::livewire.orders.templates.edit-template'
            : 'orders::livewire.orders.templates.add-template';

        return view($view_name);
    }
}
