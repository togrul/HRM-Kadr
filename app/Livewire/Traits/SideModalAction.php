<?php

namespace App\Livewire\Traits;

use Livewire\Attributes\On;

trait SideModalAction
{
    public $showSideMenu = '';

    public $modelName;

    public $secondModel;

    public bool $isSideModalOpen = false;

    public function openSideMenu($showSideMenu, $modelName = null, $secondModel = null)
    {
        $this->showSideMenu = $showSideMenu;
        $this->modelName = $modelName;
        $this->secondModel = $secondModel;
        $this->isSideModalOpen = true;

        $this->dispatch('openSideMenu', showSideMenu: $showSideMenu);
    }

    #[On('closeSideMenu')]
    public function closeSideMenu()
    {
        $this->isSideModalOpen = false;
        $this->showSideMenu = '';
        $this->modelName = null;
        $this->secondModel = null;
    }
}
