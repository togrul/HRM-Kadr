<?php

namespace App\Modules\Reports\Providers;

use App\Modules\Reports\Console\Commands\ReportsQueryBudgetCommand;
use App\Modules\Reports\Console\Commands\ReportsRenderBenchmarkCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class ReportsServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('reports')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'reports');
        $this->registerLivewireComponents();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ReportsQueryBudgetCommand::class,
                ReportsRenderBenchmarkCommand::class,
            ]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'reports');
    }

    protected function componentMap(): array
    {
        return [
            'dashboard' => \App\Modules\Reports\Livewire\Dashboard::class,
            'overview' => \App\Modules\Reports\Livewire\Overview::class,
            'standard-reports' => \App\Modules\Reports\Livewire\StandardReports::class,
            'dynamic-builder' => \App\Modules\Reports\Livewire\DynamicBuilder::class,
            'comparisons' => \App\Modules\Reports\Livewire\Comparisons::class,
        ];
    }
}
