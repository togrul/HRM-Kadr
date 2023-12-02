<?php

namespace App\Providers;

use App\Models\Menu;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        view()->composer('includes.header', function($view)
        {
            $menus = Menu::orderBy('order')->where('is_active',1)->get();

            $view->with('menus', $menus);
        });
    }
}
