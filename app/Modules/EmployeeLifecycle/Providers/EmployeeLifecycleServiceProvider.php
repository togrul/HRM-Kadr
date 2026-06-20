<?php

namespace App\Modules\EmployeeLifecycle\Providers;

use App\Modules\EmployeeLifecycle\Console\Commands\EmployeeLifecycleQueryBudgetCommand;
use App\Modules\EmployeeLifecycle\Console\Commands\SendLifecycleDeadlineRemindersCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class EmployeeLifecycleServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EmployeeLifecycleQueryBudgetCommand::class,
                SendLifecycleDeadlineRemindersCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('employee-lifecycle')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'employee-lifecycle');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'employee-lifecycle');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases([
            'dashboard' => \App\Modules\EmployeeLifecycle\Livewire\Dashboard::class,
        ], 'employee-lifecycle');
    }
}
