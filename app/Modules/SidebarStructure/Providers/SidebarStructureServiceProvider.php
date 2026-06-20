<?php

namespace App\Modules\SidebarStructure\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class SidebarStructureServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('structure')) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'structure');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $map = [
            'sidebar' => \App\Modules\SidebarStructure\Livewire\Sidebar::class,
            'orders' => \App\Modules\SidebarStructure\Livewire\Orders::class,
            'services' => \App\Modules\SidebarStructure\Livewire\Services::class,
        ];

        $this->registerAliases($map, 'structure');
    }
}
