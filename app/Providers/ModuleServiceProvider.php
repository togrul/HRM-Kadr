<?php

namespace App\Providers;

use App\Services\Modules\ModuleState;
use App\Support\Translations\ModuleTranslation;
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

        collect($state->all())
            ->filter(fn (array $entry) => ($entry['enabled'] ?? false) && ! empty($entry['provider']))
            ->each(function (array $entry, string $slug): void {
                $langPath = ModuleTranslation::langPathFromProvider((string) $entry['provider']);

                if ($langPath === null) {
                    return;
                }

                $this->loadTranslationsFrom($langPath, ModuleTranslation::namespaceFromSlug($slug));
            });

        collect($state->allEnabledProviders())
            ->unique()
            ->each(fn ($provider) => $this->app->register($provider));

        collect($state->enabledMigrationPaths())
            ->each(fn ($path) => $this->loadMigrationsFrom($path));
    }
}
