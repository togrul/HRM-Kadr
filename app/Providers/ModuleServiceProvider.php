<?php

namespace App\Providers;

use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleState::class, function () {
            return new ModuleState(config('modules.catalog', []));
        });
    }

    public function boot(): void
    {
        $state = $this->app->make(ModuleState::class);

        $catalogProviders = collect($state->allEnabledProviders());

        if (! app()->runningInConsole()) {
          dd($catalogProviders, $state->all());
      }
      

        $legacyProviders = collect(config('modules.enabled', []))
            ->filter(fn ($provider) => is_string($provider) && class_exists($provider));

        $catalogProviders
            ->merge($legacyProviders)
            ->unique()
            ->each(fn ($provider) => $this->app->register($provider));

        collect($state->enabledMigrationPaths())
            ->each(fn ($path) => $this->loadMigrationsFrom($path));
    }
}
