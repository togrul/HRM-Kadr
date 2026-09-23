<?php

namespace App\Modules\Services\Livewire\Menus;

use App\Models\Menu;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteMenu extends Component
{
    use AuthorizesRequests;
    use AuthorizesSettingsAccess;

    #[Locked]
    public ?int $menuId = null;

    #[On('setDeleteMenu')]
    public function setDeleteMenu($menuId): void
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

    public function deleteMenu(): void
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

        $this->dispatch('menuWasDeleted', __('services::menus.messages.deleted'));
    }

    public function render(): View
    {
        return view('services::livewire.services.menus.delete-menu');
    }
}
