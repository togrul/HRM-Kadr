<?php

namespace App\Modules\TrainingNeeds\Providers;

use App\Modules\TrainingNeeds\Console\Commands\TrainingNeedsQueryBudgetCommand;
use App\Modules\TrainingNeeds\Console\Commands\TrainingNeedsRenderBenchmarkCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class TrainingNeedsServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('training-needs')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'training-needs');
        $this->registerLivewireComponents();

        if ($this->app->runningInConsole()) {
            $this->commands([
                TrainingNeedsQueryBudgetCommand::class,
                TrainingNeedsRenderBenchmarkCommand::class,
            ]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'training-needs');
    }

    protected function componentMap(): array
    {
        return [
            'dashboard' => \App\Modules\TrainingNeeds\Livewire\Dashboard::class,
            'overview' => \App\Modules\TrainingNeeds\Livewire\Overview::class,
            'analytics' => \App\Modules\TrainingNeeds\Livewire\Analytics::class,
            'reports' => \App\Modules\TrainingNeeds\Livewire\Reports::class,
            'lists' => \App\Modules\TrainingNeeds\Livewire\Lists::class,
            'results-summary' => \App\Modules\TrainingNeeds\Livewire\ResultsSummary::class,
            'session-detail-workspace' => \App\Modules\TrainingNeeds\Livewire\SessionDetailWorkspace::class,
            'certificate-viewer' => \App\Modules\TrainingNeeds\Livewire\CertificateViewer::class,
        ];
    }
}
