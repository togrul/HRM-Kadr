<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\Setting;
use App\Services\NumberToWordsService;
use App\Services\Features\FeatureState;
use App\Services\Modules\ModuleState;
use App\Services\Profiles\ProfileState;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use function Laravel\Prompts\confirm;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProfileState::class, fn () => new ProfileState(
            config('profiles.profiles', []),
            config('profiles.active', env('APP_PROFILE', 'default')),
            config('modules.catalog', []),
        ));

        $this->app->singleton(NumberToWordsService::class, fn () => new NumberToWordsService);
        $this->app->singleton(StructureService::class, fn () => new StructureService);
        $this->app->singleton(ModuleState::class, fn () => new ModuleState($this->app->make(ProfileState::class)->modules()));
        $this->app->singleton(FeatureState::class, fn () => new FeatureState($this->app->make(ProfileState::class)->features()));
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
        $this->registerViewComposers();
        $this->registerMacros();
        $this->registerBladeDirectives();

    }

    private function registerViewComposers(): void
    {
        // Share menus with the header view
        view()->composer('includes.header', function ($view) {
            $menus = Cache::rememberForever('menus:header', function () {
                return Menu::with('permission')
                    ->active()
                    ->ordered()
                    ->get();
            });

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
        Builder::macro('accessible', function (?User $user = null) {
            $ids = resolve(StructureService::class)->getAccessibleStructures($user);
            return empty($ids) ? $this : $this->whereIn('id', $ids);
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

    private function registerBladeDirectives(): void
    {
        if (! function_exists('module_enabled')) {
            /**
             * Check if a module is enabled.
             */
            function module_enabled(string $slug): bool
            {
                return app(\App\Services\Modules\ModuleState::class)->enabled($slug);
            }
        }

        if (! function_exists('feature_enabled')) {
            /**
             * Check if a feature flag is enabled.
             */
            function feature_enabled(string $feature): bool
            {
                return app(\App\Services\Features\FeatureState::class)->enabled($feature);
            }
        }

        Blade::if('module', fn (string $slug) => module_enabled($slug));
        Blade::if('feature', fn (string $feature) => feature_enabled($feature));
    }
}
