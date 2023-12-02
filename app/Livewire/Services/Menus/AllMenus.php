<?php

namespace App\Livewire\Services\Menus;
use App\Livewire\Traits\SideModalAction;
use App\Models\Menu;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Livewire\Component;

class AllMenus extends Component
{
    use SideModalAction,AuthorizesRequests;

    protected $listeners = ['menuAdded' => '$refresh','menuWasDeleted' => '$refresh'];

    public function setDeleteMenu($menuId)
    {
        $this->dispatch('setDeleteMenu',$menuId);
    }

    public function render()
    {
        $_menus = Menu::all();

        return view('livewire.services.menus.all-menus',compact('_menus'));
    }
}
