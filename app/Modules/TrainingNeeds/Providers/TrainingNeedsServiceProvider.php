<?php

namespace App\Modules\TrainingNeeds\Providers;

use App\Modules\TrainingNeeds\Console\Commands\TrainingNeedsQueryBudgetCommand;
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
            'lists' => \App\Modules\TrainingNeeds\Livewire\Lists::class,
            'certificate-viewer' => \App\Modules\TrainingNeeds\Livewire\CertificateViewer::class,
        ];
    }
}
