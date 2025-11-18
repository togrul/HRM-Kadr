<?php

namespace App\Livewire\Traits;

use App\Models\OrderType;
use App\Models\Rank;
use App\Traits\NormalizesDropdownPayloads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait ComponentCrud
{
    use DropdownConstructTrait;
    use NormalizesDropdownPayloads;

    public $component = [];

    public $searchOrder = '';

    public $title;

    public $componentModel;

    public function rules()
    {
        return [
            'component.name' => 'required|string|min:2',
            'component.content' => 'required',
            'component.order_type_id' => 'required|int|exists:order_types,id',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'component.name' => __('Name'),
            'component.content' => __('Content'),
            'component.order_type_id' => __('Category'),
        ];
    }

    public function updated($name, $value)
    {
        $data = ($this->component['title'] ?? '').' '.($this->component['content'] ?? '');

        $dollarStrings = array_filter(
            array_map(
                function ($string) {
                    return Str::startsWith($string, '$') ? $string : null;
                },
                explode(' ', str_replace(['“', '”'], '', trim($data)))
            )
        );

        $this->component['dynamic_fields'] = implode(',', array_unique($dollarStrings));
    }

    public function mount()
    {
        if (! empty($this->componentModel)) {
            $this->fillComponent();
            $this->title = __('Edit component');
        } else {
            $this->title = __('Add component');
            $this->component['order_type_id'] = null;
            $this->component['rank_id'] = null;
        }
    }

    #[Computed]
    public function orderOptions(): array
    {
        $selected = data_get($this->component, 'order_type_id');
        $search = $this->dropdownSearch('searchOrder');

        $base = OrderType::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: "component:order_type",
                base: $base,
                selectedId: $selected,
                limit: 60
            );
        }

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $search,
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed]
    public function rankOptions(): array
    {
        $selected = data_get($this->component, 'rank_id');

        $locale = app()->getLocale();
        $labelColumn = 'name_'.$locale;

        $base = Rank::query()
            ->select('id', DB::raw("$labelColumn as label"))
            ->where('is_active', 1)
            ->orderBy('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: $labelColumn,
            searchTerm: '',
            selectedId: $selected,
            limit: 50
        );
    }

    public function render()
    {
        $view_name = ! empty($this->candidateModel)
            ? 'livewire.services.components.edit-component'
            : 'livewire.services.components.add-component';

        return view($view_name);
    }
}
