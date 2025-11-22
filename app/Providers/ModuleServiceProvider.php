<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        collect(config('modules.enabled', []))
            ->filter(fn ($provider) => is_string($provider) && class_exists($provider))
            ->each(fn ($provider) => $this->app->register($provider));
    }
}
