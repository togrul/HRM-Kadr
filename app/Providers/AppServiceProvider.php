<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\Setting;
use App\Observers\SettingsObserver;
use App\Services\NumberToWordsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NumberToWordsService::class, function ($app) {
            return new NumberToWordsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Setting::observe(SettingsObserver::class);
        view()->composer('includes.header', function($view)
        {
            $menus = Menu::orderBy('order')->where('is_active',1)->get();

            $view->with('menus', $menus);
        });

        view()->composer('*',function($view)
        {
            $_settings = Cache::rememberForever('settings',function () {
                return Setting::pluck('value','name')->toArray();
            });

            $view->with(compact('_settings'));
        });
    }
}
