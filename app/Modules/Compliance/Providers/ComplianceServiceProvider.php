<?php

namespace App\Modules\Compliance\Providers;

use App\Modules\Compliance\Console\Commands\ComplianceDocumentQueryBudgetCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class ComplianceServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ComplianceDocumentQueryBudgetCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('compliance')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'compliance');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'compliance');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases([
            'document-expiry-dashboard' => \App\Modules\Compliance\Livewire\DocumentExpiryDashboard::class,
        ], 'compliance');
    }
}
