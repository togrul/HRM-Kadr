<?php

namespace App\Modules\Services\Livewire\Menus;

use App\Models\Menu;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use App\Livewire\Traits\DropdownConstructTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EditMenu extends Component
{
    use AuthorizesRequests;
    use DropdownConstructTrait;

    public $menuModel;

    public $title;

    public $menu;

    public string $searchPermission = '';

    protected function rules()
    {
        return [
            'menu.name' => 'required|string|min:1',
            'menu.color' => 'required|string|min:1',
            'menu.order' => 'required|integer',
            'menu.url' => 'required|string|min:1',
            'menu.icon' => 'required|string|min:1',
            'menu.permission_id' => 'required|integer|exists:permissions,id'
        ];
    }

    protected function validationAttributes()
    {
        return [
            'menu.name' => __('Name'),
            'menu.color' => __('Color'),
            'menu.order' => __('Order'),
            'menu.url' => __('URL'),
            'menu.icon' => __('Icon'),
            'menu.permission_id' => __('Permissions')
        ];
    }

    public function mount()
    {
        // $this->authorize('manage-settings',$this->user);
        $this->title = __('Edit menu');
        $this->menuModel = Menu::with('permission')->where('id', $this->menuModel['id'])->first();

        $this->menu['name'] = $this->menuModel->name;
        $this->menu['url'] = $this->menuModel->url;
        $this->menu['color'] = $this->menuModel->color;
        $this->menu['order'] = $this->menuModel->order;
        $this->menu['icon'] = $this->menuModel->icon;
        $this->menu['is_active'] = $this->menuModel->is_active == 1;
        $this->menu['permission_id'] = $this->menuModel->permission_id;
    }

    public function store()
    {
        $this->validate();
        $this->menuModel->update($this->menu);

        $this->dispatch('menuAdded', __('Menu was updated successfully!'));
    }

    public function render()
    {
        return view('services::livewire.services.menus.edit-menu');
    }

    #[\Livewire\Attributes\Computed]
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
}
