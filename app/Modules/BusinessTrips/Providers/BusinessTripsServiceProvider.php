<?php

namespace App\Modules\BusinessTrips\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class BusinessTripsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'business-trips');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('business-trips.list', \App\Modules\BusinessTrips\Livewire\BusinessTrips::class);
    }
}
