<?php

namespace App\Modules\Leaves\Providers;

use App\Contracts\AbsenceSource;
use App\Models\Leave;
use App\Modules\Leaves\Application\Services\LeaveAbsenceSource;
use App\Modules\Leaves\Application\Services\LeaveListCacheVersion;
use App\Modules\Leaves\Console\Commands\LeavesQueryBudgetCommand;
use App\Modules\Leaves\Console\Commands\LeavesRenderBenchmarkCommand;
use App\Observers\LeaveObserver;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class LeavesServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LeavesQueryBudgetCommand::class,
                LeavesRenderBenchmarkCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('leaves')) {
            return;
        }

        // Contributes this module's absences to the cross-module overlap check.
        $this->app->tag([LeaveAbsenceSource::class], AbsenceSource::TAG);
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'leaves');
        $this->loadMigrations();
        $this->registerObservers();
        $this->registerPolicies();
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

        // Any leave write invalidates the list's page/stat caches.
        $bump = function (): void {
            $this->app->make(LeaveListCacheVersion::class)->bump();
        };
        Leave::saved($bump);
        Leave::deleted($bump);
        Leave::restored($bump);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(
            \App\Models\Leave::class,
            \App\Modules\Leaves\Policies\LeavePolicy::class
        );
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
