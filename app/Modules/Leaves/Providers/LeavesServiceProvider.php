<?php

namespace App\Modules\Leaves\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LeavesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'leaves');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('leaves.leaves', \App\Modules\Leaves\Livewire\Leaves::class);
        Livewire::component('leaves.add-leave', \App\Modules\Leaves\Livewire\AddLeave::class);
        Livewire::component('leaves.edit-leave', \App\Modules\Leaves\Livewire\EditLeave::class);
        Livewire::component('leaves.delete-leave', \App\Modules\Leaves\Livewire\DeleteLeave::class);
    }
}
