<?php

namespace App\Modules\Payroll\Providers;

use App\Modules\Payroll\Console\Commands\PayrollQueryBudgetCommand;
use App\Modules\Payroll\Console\Commands\PayrollRenderBenchmarkCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class PayrollServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PayrollQueryBudgetCommand::class,
                PayrollRenderBenchmarkCommand::class,
            ]);
        }

        $this->app->bind(
            \App\Modules\Payroll\Domain\Contracts\PayslipReadRepository::class,
            \App\Modules\Payroll\Infrastructure\Persistence\Eloquent\EloquentPayslipReadRepository::class,
        );
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('payroll')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'payroll');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'payroll');
        $this->loadMigrations();
        $this->registerAliases($this->componentMap(), 'payroll');
    }

    private function loadMigrations(): void
    {
        $path = $this->app->make(ModuleState::class)->migrationPath('payroll');

        if ($path) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function componentMap(): array
    {
        return [
            'dashboard' => \App\Modules\Payroll\Livewire\Dashboard::class,
        ];
    }
}
