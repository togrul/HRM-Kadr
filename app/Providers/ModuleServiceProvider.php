<?php

namespace App\Providers;

use App\Services\Modules\ModuleState;
use App\Services\Profiles\ProfileState;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleState::class, function ($app) {
            $profileState = $app->make(ProfileState::class);

            return new ModuleState($profileState->modules());
        });
    }

    public function boot(): void
    {
        $state = $this->app->make(ModuleState::class);

        collect($state->allEnabledProviders())
            ->unique()
            ->each(fn ($provider) => $this->app->register($provider));

        collect($state->enabledMigrationPaths())
            ->each(fn ($path) => $this->loadMigrationsFrom($path));
    }
}
