<?php

namespace App\Modules\Services\Livewire\Menus;
use App\Models\Menu;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteMenu extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $menuId = null;

    #[On('setDeleteMenu')]
    public function setDeleteMenu($menuId)
    {
        $menu = Menu::query()
            ->select('id')
            ->find($menuId);

        if (! $menu) {
            $this->menuId = null;

            return;
        }

        // $this->authorize('delete', $menu);

        $this->menuId = (int) $menu->id;

        $this->dispatch('deleteMenuWasSet');
    }

    public function deleteMenu()
    {
        if (! $this->menuId) {
            return;
        }

        $menu = Menu::query()
            ->select('id')
            ->find($this->menuId);

        if (! $menu) {
            $this->menuId = null;

            return;
        }

        // $this->authorize('delete', $menu);

        $menu->delete();

        $this->menuId = null;

        $this->dispatch('menuWasDeleted', __('Menu was deleted!'));
    }

    public function render()
    {
        return view('services::livewire.services.menus.delete-menu');
    }
}
