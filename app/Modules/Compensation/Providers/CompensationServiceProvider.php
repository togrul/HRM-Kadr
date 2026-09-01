<?php

namespace App\Modules\Compensation\Providers;

use App\Modules\Compensation\Console\Commands\CompensationQueryBudgetCommand;
use App\Modules\Compensation\Console\Commands\CompensationRenderBenchmarkCommand;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\Compensation\Infrastructure\Persistence\Eloquent\EloquentCompensationReadRepository;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class CompensationServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CompensationQueryBudgetCommand::class,
                CompensationRenderBenchmarkCommand::class,
            ]);
        }

        $this->app->bind(CompensationReadRepository::class, EloquentCompensationReadRepository::class);
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('compensation')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'compensation');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'compensation');
        $this->loadMigrations();
        $this->registerAliases($this->componentMap(), 'compensation');
    }

    private function loadMigrations(): void
    {
        $path = $this->app->make(ModuleState::class)->migrationPath('compensation');

        if ($path) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function componentMap(): array
    {
        return [
            'dashboard' => \App\Modules\Compensation\Livewire\Dashboard::class,
        ];
    }
}
