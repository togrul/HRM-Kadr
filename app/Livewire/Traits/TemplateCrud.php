<?php

namespace App\Livewire\Traits;

use App\Models\OrderCategory;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;

trait TemplateCrud
{
    use SelectListTrait,WithFileUploads;
    public $template_data = [];

    public $categoryId,$categoryName,$searchCategory;
    public $title;
    public $templateModel;

    public function rules()
    {
        return [
            'template_data.id' => 'required|int|min:2|unique:orders,id'. (!empty($this->templateModel) ? ','.$this->templateModel : ''),
            'template_data.name' => 'required|string|min:2',
            'template_data.content' => 'required',
            'template_data.order_category_id.id' => 'required|int|exists:order_categories,id',
            'template_data.order_model' => 'required|string'
        ];
    }

    protected function validationAttributes()
    {
        return [
            'template_data.id' => __('Id'),
            'template_data.name' => __('Name'),
            'template_data.content' => __('Content'),
            'template_data.order_category_id.id' => __('Category'),
            'template_data.order_model' => __('Model'),
        ];
    }

    public function mount()
    {
        $this->categoryName = '---';
        if(!empty($this->templateModel))
        {
            $this->fillTemplate();
            $this->title = __('Edit template');
        }
        else
        {
            $this->title = __('Add template');
        }
    }

    public function render()
    {
        $_categories = OrderCategory::select('id',DB::raw('name_'.config('app.locale').' as name'))
            ->when(!empty($this->searchCategory),function($q){
                $q->where('name_'.config('app.locale'),'LIKE',"%{$this->searchCategory}%");
            })
            ->get();

        $view_name = !empty($this->templateModel)
            ? 'livewire.orders.templates.edit-template'
            : 'livewire.orders.templates.add-template';

        return view($view_name,compact('_categories'));
    }
}
