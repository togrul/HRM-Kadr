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
            'component.title' => 'nullable|string|max:5000',
            'component.dynamic_fields' => 'nullable|string|max:2000',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'component.name' => __('Name'),
            'component.content' => __('Content'),
            'component.order_type_id' => __('Category'),
            'component.title' => __('Title'),
            'component.dynamic_fields' => __('Dynamic fields'),
        ];
    }

    public function updated($name, $value)
    {
        if (! in_array($name, ['component.content', 'component.title'], true)) {
            return;
        }

        $data = (string) (($this->component['title'] ?? '').' '.($this->component['content'] ?? ''));
        $tokens = $this->extractDynamicTokens($data);
        $this->component['dynamic_fields'] = implode(',', $tokens);
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
        $view_name = ! empty($this->componentModel)
            ? 'services::livewire.services.components.edit-component'
            : 'services::livewire.services.components.add-component';

        return view($view_name);
    }

    public function dynamicFieldTokens(): array
    {
        $raw = (string) ($this->component['dynamic_fields'] ?? '');

        return collect(explode(',', $raw))
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function extractDynamicTokens(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        preg_match_all('/\$\{?[a-zA-Z0-9_#]+\}?/u', $content, $matches);
        $found = $matches[0] ?? [];

        return collect($found)
            ->map(function (string $raw): string {
                $token = trim($raw);
                $token = trim($token, '{}');
                $token = ltrim($token, '$');
                $token = preg_replace('/#\d+$/', '', $token);
                if (! is_string($token) || trim($token) === '') {
                    return '';
                }

                return '$'.trim($token);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
