<?php

namespace App\Modules\OnboardingLibrary\Providers;

use App\Modules\OnboardingLibrary\Console\Commands\OnboardingLibraryAutomationCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class OnboardingLibraryServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                OnboardingLibraryAutomationCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('onboarding-library')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'onboarding-library');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'onboarding-library');
        $this->loadMigrations();
        $this->registerAliases($this->componentMap(), 'onboarding-library');
    }

    private function loadMigrations(): void
    {
        $path = $this->app->make(ModuleState::class)->migrationPath('onboarding-library');

        if ($path) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function componentMap(): array
    {
        return [
            'dashboard' => \App\Modules\OnboardingLibrary\Livewire\Dashboard::class,
        ];
    }
}
