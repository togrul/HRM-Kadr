<?php

namespace App\Modules\SidebarStructure\Providers;

use App\Models\Structure;
use App\Observers\StructureObserver;
use App\Providers\Concerns\RegistersLivewireAliases;
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
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'structure');
        $this->registerObservers();
        $this->registerLivewireComponents();
    }

    protected function registerObservers(): void
    {
        Structure::observe(StructureObserver::class);
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
