<?php

namespace App\Modules\BusinessTrips\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class BusinessTripsServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('business-trips')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'business-trips');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'business-trips');
    }

    protected function componentMap(): array
    {
        return [
            'list' => \App\Modules\BusinessTrips\Livewire\BusinessTrips::class,
        ];
    }
}
