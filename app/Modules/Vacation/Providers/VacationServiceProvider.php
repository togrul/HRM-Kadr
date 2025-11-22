<?php

namespace App\Modules\Vacation\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use Illuminate\Support\ServiceProvider;

class VacationServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

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
        $this->registerAliases($this->componentMap(), 'vacation');
    }

    protected function componentMap(): array
    {
        return [
            'vacations' => \App\Modules\Vacation\Livewire\Vacations::class,
        ];
    }
}
