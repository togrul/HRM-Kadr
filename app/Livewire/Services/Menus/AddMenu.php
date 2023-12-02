<?php

namespace App\Livewire\Services\Menus;

use App\Models\Menu;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AddMenu extends Component
{
    use AuthorizesRequests;

    public $title;
    public $menu = [];

    protected function rules()
    {
        return [
           'menu.name'=> 'required|string|min:1',
           'menu.color' => 'required|string|min:1',
           'menu.order' => 'required|integer',
           'menu.url' => 'required|string|min:1',
           'menu.icon' => 'required|string|min:1',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'menu.name'=>__('Name'),
            'menu.color' => __('Color'),
            'menu.order'=> __('Order'),
            'menu.url'=> __('URL'),
            'menu.icon'=> __('Icon'),
        ];
    }

    public function store()
    {
        $this->validate();

        Menu::create($this->menu);

        $this->dispatch('menuAdded',__('Menu was added successfully!'));
    }

    public function mount()
    {
        // $this->authorize('manage-settings',$this->user);
        $this->title = __('Add menu');
    }

    public function render()
    {
        return view('livewire.services.menus.add-menu',);
    }
}
