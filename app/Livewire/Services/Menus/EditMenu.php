<?php

namespace App\Livewire\Services\Menus;

use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Livewire\Traits\SelectListTrait;
use App\Models\Menu;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class EditMenu extends Component
{
    use AuthorizesRequests;
    use SelectListTrait;
    use FillComplexArrayTrait;

    public $menuModel;

    public $title;

    public $menu;

    protected function rules()
    {
        return [
            'menu.name' => 'required|string|min:1',
            'menu.color' => 'required|string|min:1',
            'menu.order' => 'required|integer',
            'menu.url' => 'required|string|min:1',
            'menu.icon' => 'required|string|min:1',
            'menu.permission_id.id' => 'required|integer|exists:permissions,id'
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
            'menu.permission_id.id' => __('Permissions')
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
        $this->menu['is_active'] = $this->menuModel->is_active == 1 ? true : false;
        $this->handleRelatedEntity(
            entity: 'permission',
            field: 'permission_id',
            fillTo: 'menu',
            getFrom: $this->menuModel->toArray(),
            titleField: 'name',
            hasSelectedField: false
        );
    }

    public function store()
    {
        $this->validate();
        $data = $this->menu;
        $data['permission_id'] = $data['permission_id']['id'];
        $this->menuModel->update($data);

        $this->dispatch('menuAdded', __('Menu was updated successfully!'));
    }

    public function render()
    {
        $permissions = Permission::select('id','name')->get();

        return view('livewire.services.menus.edit-menu', compact('permissions'));
    }
}
