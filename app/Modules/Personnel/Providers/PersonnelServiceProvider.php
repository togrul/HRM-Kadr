<?php

namespace App\Modules\Personnel\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PersonnelServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('personnel')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/print.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'personnel');
        $this->registerPolicies();
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'personnel');
    }

    protected function registerPolicies(): void
    {
        Gate::policy(\App\Models\Personnel::class, \App\Modules\Personnel\Policies\PersonnelPolicy::class);
    }

    protected function componentMap(): array
    {
        return [
            'all-personnel' => \App\Modules\Personnel\Livewire\AllPersonnel::class,
            'add-personnel' => \App\Modules\Personnel\Livewire\AddPersonnel::class,
            'edit-personnel' => \App\Modules\Personnel\Livewire\EditPersonnel::class,
            'delete-personnel' => \App\Modules\Personnel\Livewire\DeletePersonnel::class,
            'files' => \App\Modules\Personnel\Livewire\Files::class,
            'information' => \App\Modules\Personnel\Livewire\Information::class,
            'vacation-list' => \App\Modules\Personnel\Livewire\VacationList::class,
        ];
    }

}
