<?php

namespace App\Livewire\Traits;

use Livewire\Attributes\On;

trait SideModalAction
{
    public $showSideMenu = '';

    public $modelName;

    public $secondModel;

    public bool $isSideModalOpen = false;

    public function openSideMenu($showSideMenu, $modelName = null, $secondModel = null): void
    {
        $this->showSideMenu = $showSideMenu;
        $this->modelName = $modelName;
        $this->secondModel = $secondModel;
        $this->isSideModalOpen = true;
        $this->dispatch('openSideMenu', showSideMenu: $showSideMenu);
    }

    /**
     * A deep link (?create=1) opened a form on arrival; drop its query parameters from the
     * address bar so a reload or a shared link does not open the form again.
     */
    protected function forgetDeepLinkParams(string ...$params): void
    {
        $this->js('const u = new URL(window.location.href); '.json_encode(array_values($params)).'.forEach((p) => u.searchParams.delete(p)); window.history.replaceState(window.history.state, "", u);');
    }

    #[On('closeSideMenu')]
    public function closeSideMenu(): void
    {
        $this->isSideModalOpen = false;
        $this->showSideMenu = '';
        $this->modelName = null;
        $this->secondModel = null;
    }

    #[On([
        'ui:modal-close',
        'personnelAdded',
        'permissionSet',
        'staffAdded',
        'userAdded',
        'menuAdded',
        'fileAdded',
        'candidateAdded',
        'templateAdded',
        'componentAdded',
        'orderAdded',
        'rankAdded',
        'leaveAdded',
        'leaveUpdated',
    ])]
    public function closeSideMenuAfterModalEvent(): void
    {
        if (! $this->isSideModalOpen) {
            return;
        }

        $this->closeSideMenu();
    }
}
