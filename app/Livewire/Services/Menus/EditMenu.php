<?php

namespace App\Livewire\Services\Menus;

use App\Models\Menu;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class EditMenu extends Component
{
    use AuthorizesRequests;

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
        ];
    }

    public function mount()
    {
        // $this->authorize('manage-settings',$this->user);
        $this->title = __('Edit menu');
        $this->menuModel = Menu::where('id', $this->menuModel['id'])->first();

        $this->menu['name'] = $this->menuModel->name;
        $this->menu['url'] = $this->menuModel->url;
        $this->menu['color'] = $this->menuModel->color;
        $this->menu['order'] = $this->menuModel->order;
        $this->menu['icon'] = $this->menuModel->icon;
        $this->menu['is_active'] = $this->menuModel->is_active == 1 ? true : false;
    }

    public function store()
    {
        $this->validate();

        $this->menuModel->update($this->menu);

        $this->dispatch('menuAdded', __('Menu was updated successfully!'));
    }

    public function render()
    {
        return view('livewire.services.menus.edit-menu');
    }
}
