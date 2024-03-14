<?php

namespace App\Livewire\Services\Menus;

use App\Models\Menu;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteMenu extends Component
{
    public ?Menu $menu;

    #[On('setDeleteMenu')]
    public function setDeleteMenu($menuId)
    {
        $this->menu = Menu::findOrFail($menuId);

        $this->dispatch('deleteMenuWasSet');
    }

    public function deleteMenu()
    {
        // $this->authorize('delete',$this->comment);

        Menu::destroy($this->menu->id);

        $this->menu = null;

        $this->dispatch('menuWasDeleted' , __('Menu was deleted!'));
    }

    public function render()
    {
        return view('livewire.services.menus.delete-menu');
    }
}
