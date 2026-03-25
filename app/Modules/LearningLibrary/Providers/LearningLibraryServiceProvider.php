<?php

namespace App\Modules\LearningLibrary\Providers;

use App\Modules\LearningLibrary\Console\Commands\LearningLibraryAutomationCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class LearningLibraryServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LearningLibraryAutomationCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('learning-library')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'learning-library');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'learning-library');
        $this->loadMigrations();
        $this->registerAliases($this->componentMap(), 'learning-library');
    }

    private function loadMigrations(): void
    {
        $path = $this->app->make(ModuleState::class)->migrationPath('learning-library');

        if ($path) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function componentMap(): array
    {
        return [
            'dashboard' => \App\Modules\LearningLibrary\Livewire\Dashboard::class,
        ];
    }
}
