<?php

namespace App\Modules\Leaves\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use App\Models\Leave;
use App\Observers\LeaveObserver;
use Illuminate\Support\ServiceProvider;

class LeavesServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('leaves')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'leaves');
        $this->loadMigrations();
        $this->registerObservers();
        $this->registerLivewireComponents();
    }

    protected function loadMigrations(): void
    {
        $path = $this->app->make(ModuleState::class)->migrationPath('leaves');

        if ($path) {
            $this->loadMigrationsFrom($path);
        }
    }

    protected function registerObservers(): void
    {
        Leave::observe(LeaveObserver::class);
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'leaves');
    }

    protected function componentMap(): array
    {
        return [
            'leaves' => \App\Modules\Leaves\Livewire\Leaves::class,
            'add-leave' => \App\Modules\Leaves\Livewire\AddLeave::class,
            'edit-leave' => \App\Modules\Leaves\Livewire\EditLeave::class,
            'delete-leave' => \App\Modules\Leaves\Livewire\DeleteLeave::class,
        ];
    }
}
