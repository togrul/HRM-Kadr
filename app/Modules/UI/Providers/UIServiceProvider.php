<?php

namespace App\Modules\UI\Providers;

use Illuminate\Support\ServiceProvider;

class UIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'ui');
    }
}
