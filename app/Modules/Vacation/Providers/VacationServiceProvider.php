<?php

namespace App\Modules\Vacation\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class VacationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'vacation');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('vacation.vacations', \App\Modules\Vacation\Livewire\Vacations::class);
    }
}
