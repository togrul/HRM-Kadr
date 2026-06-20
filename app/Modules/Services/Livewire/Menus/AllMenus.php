<?php

namespace App\Modules\Services\Livewire\Menus;

use App\Livewire\Traits\SideModalAction;
use App\Models\Menu;
use App\Support\Navigation\MenuPresentation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['menuAdded', 'menuWasDeleted'])]
class AllMenus extends Component
{
    use AuthorizesRequests,SideModalAction;

    public function displayMenuName(Menu $menu): string
    {
        return MenuPresentation::label((string) $menu->name);
    }

    public function displayMenuIconComponent(Menu $menu): string
    {
        return MenuPresentation::iconComponent($menu);
    }

    public function setDeleteMenu($menuId)
    {
        $this->dispatch('setDeleteMenu', $menuId);
    }

    public function render()
    {
        $_menus = Menu::all();

        return view('services::livewire.services.menus.all-menus', compact('_menus'));
    }
}
