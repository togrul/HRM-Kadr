<?php

namespace App\Livewire\Services\Menus;

use App\Livewire\Traits\SelectListTrait;
use App\Models\Menu;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class AddMenu extends Component
{
    use AuthorizesRequests;
    use SelectListTrait;

    public $title;

    public $menu = [];

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

    public function store()
    {
        $this->validate();

        $data = $this->menu;
        $data['permission_id'] = $data['permission_id']['id'];
        Menu::create($data);

        $this->dispatch('menuAdded', __('Menu was added successfully!'));
    }

    public function mount()
    {
        // $this->authorize('manage-settings',$this->user);
        $this->title = __('Add menu');
    }

    public function render()
    {
        $permissions = Permission::select('id','name')->get();
        return view('livewire.services.menus.add-menu', compact('permissions'));
    }
}
