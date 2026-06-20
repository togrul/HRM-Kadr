<?php

namespace App\Modules\PerformanceEvaluation\Providers;

use App\Modules\PerformanceEvaluation\Console\Commands\PerformanceEvaluationQueryBudgetCommand;
use App\Modules\PerformanceEvaluation\Console\Commands\PerformanceEvaluationRenderBenchmarkCommand;
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
                PerformanceEvaluationRenderBenchmarkCommand::class,
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
            'foundation-workspace' => \App\Modules\PerformanceEvaluation\Livewire\FoundationWorkspace::class,
            'operations-workspace' => \App\Modules\PerformanceEvaluation\Livewire\OperationsWorkspace::class,
            'evaluator-workspace' => \App\Modules\PerformanceEvaluation\Livewire\EvaluatorWorkspace::class,
            'test-workspace' => \App\Modules\PerformanceEvaluation\Livewire\TestWorkspace::class,
            'user-personnel-links' => \App\Modules\PerformanceEvaluation\Livewire\UserPersonnelLinks::class,
            'evaluator-score-capture' => \App\Modules\PerformanceEvaluation\Livewire\EvaluatorScoreCapture::class,
            'overview' => \App\Modules\PerformanceEvaluation\Livewire\Overview::class,
            'evaluations-summary' => \App\Modules\PerformanceEvaluation\Livewire\EvaluationsSummary::class,
            'tests-summary' => \App\Modules\PerformanceEvaluation\Livewire\TestsSummary::class,
            'reports' => \App\Modules\PerformanceEvaluation\Livewire\Reports::class,
            'lists' => \App\Modules\PerformanceEvaluation\Livewire\Lists::class,
        ];
    }
}
