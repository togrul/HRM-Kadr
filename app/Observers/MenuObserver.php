<?php

namespace App\Observers;

use App\Models\Menu;
use App\Traits\ObservableTrait;

class MenuObserver
{
    use ObservableTrait;

    protected array $caches = [
        'menus:header',
    ];

    public function created(Menu $menu): void
    {
        $this->clearCaches();
    }

    public function updated(Menu $menu): void
    {
        $this->clearCaches();
    }

    public function deleted(Menu $menu): void
    {
        $this->clearCaches();
    }
}
