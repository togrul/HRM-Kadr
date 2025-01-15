<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\Setting;
use App\Observers\SettingsObserver;
use App\Services\NumberToWordsService;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NumberToWordsService::class, fn () => new NumberToWordsService);
        $this->app->singleton(StructureService::class, fn () => new StructureService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //        DB::prohibitDestructiveCommands(
        //            $this->app->isProduction(),
        //        );
        $this->configureModels();
        $this->registerObservers();
        $this->registerViewComposers();
        $this->registerMacros();

    }

    /**
     * Register observers.
     */
    private function registerObservers(): void
    {
        Setting::observe(SettingsObserver::class);
    }

    private function registerViewComposers(): void
    {
        // Share menus with the header view
        view()->composer('includes.header', function ($view) {
            $menus = Menu::with('permission')
                ->active()
                ->ordered()
                ->get();

            $view->with('menus', $menus);
        });

        // Share settings globally across all views
        view()->composer('*', function ($view) {
            $settings = Cache::rememberForever('settings', fn () => Setting::pluck('value', 'name')->toArray());
            $view->with('_settings', $settings);
        });
    }

    /**
     * Register query builder macros.
     */
    private function registerMacros(): void
    {
        Builder::macro('accessible', function () {
            if (auth()->check()) {
                $accessibleIds = resolve(StructureService::class)->getAccessibleStructures();

                return $this->whereIn('id', $accessibleIds);
            }

            return $this;
        });
    }

    private function configureModels(): void
    {
        if (! $this->app->isProduction()) {
            Model::shouldBeStrict();

            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
                $class = $model::class;

                info("Attempted to lazy load [$relation] on model [$class]");
            });
        }
    }
}
