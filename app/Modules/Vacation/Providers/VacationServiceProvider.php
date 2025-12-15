<?php

namespace App\Modules\Vacation\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\Facades\Gate;
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
        if (! $this->app->make(ModuleState::class)->enabled('vacation')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'vacation');
        $this->registerPolicies();
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

    protected function registerPolicies(): void
    {
        Gate::policy(\App\Models\PersonnelVacation::class, \App\Modules\Vacation\Policies\VacationPolicy::class);
    }
}
