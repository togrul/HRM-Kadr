<?php

namespace App\Modules\Services\Livewire\Menus;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Menu;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class AddMenu extends Component
{
    use AuthorizesRequests;
    use DropdownConstructTrait;

    public $title;

    public $menu = [
        'permission_id' => null,
    ];

    public string $searchPermission = '';

    protected function rules()
    {
        return [
            'menu.name' => 'required|string|min:1',
            'menu.color' => 'required|string|min:1',
            'menu.order' => 'required|integer',
            'menu.url' => 'required|string|min:1',
            'menu.icon' => 'required|string|min:1',
            'menu.permission_id' => 'required|integer|exists:permissions,id',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'menu.name' => __('services::common.labels.name'),
            'menu.color' => __('services::common.labels.color'),
            'menu.order' => __('services::common.labels.order'),
            'menu.url' => __('services::common.labels.url'),
            'menu.icon' => __('services::common.labels.icon'),
            'menu.permission_id' => __('services::common.labels.permissions'),
        ];
    }

    #[Computed]
    public function permissionOptions(): array
    {
        $selected = data_get($this->menu, 'permission_id');
        $search = $this->dropdownSearch('searchPermission');

        $base = Permission::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('name');

        if ($search === '') {
            return $this->cachedOptionsWithSelected(
                cacheKey: 'menus:permissions',
                base: $base,
                selectedId: $selected,
                limit: 80
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

    public function store()
    {
        $this->validate();

        Menu::create($this->menu);

        $this->dispatch('menuAdded', __('services::menus.messages.created'));
    }

    public function mount()
    {
        $this->title = __('services::menus.titles.add');
    }

    public function render()
    {
        return view('services::livewire.services.menus.add-menu');
    }
}
