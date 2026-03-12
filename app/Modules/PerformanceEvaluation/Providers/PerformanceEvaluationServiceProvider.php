<?php

namespace App\Modules\PerformanceEvaluation\Providers;

use App\Modules\PerformanceEvaluation\Console\Commands\PerformanceEvaluationQueryBudgetCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class PerformanceEvaluationServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('performance-evaluation')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'performance-evaluation');
        $this->registerLivewireComponents();

        if ($this->app->runningInConsole()) {
            $this->commands([
                PerformanceEvaluationQueryBudgetCommand::class,
            ]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'performance-evaluation');
    }

    protected function componentMap(): array
    {
        return [
            'dashboard' => \App\Modules\PerformanceEvaluation\Livewire\Dashboard::class,
            'evaluator-workspace' => \App\Modules\PerformanceEvaluation\Livewire\EvaluatorWorkspace::class,
            'lists' => \App\Modules\PerformanceEvaluation\Livewire\Lists::class,
        ];
    }
}
