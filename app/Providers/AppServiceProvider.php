<?php

namespace App\Providers;

use App\Models\City;
use App\Models\CountryTranslation;
use App\Models\EducationDegree;
use App\Models\Menu;
use App\Models\Position;
use App\Models\RankReason;
use App\Models\Setting;
use App\Models\SocialOrigin;
use App\Models\Structure;
use App\Models\WorkNorm;
use App\Models\Disability;
use App\Observers\CityObserver;
use App\Observers\CountryTranslationObserver;
use App\Observers\EducationDegreeObserver;
use App\Observers\PositionObserver;
use App\Observers\RankReasonObserver;
use App\Observers\SettingsObserver;
use App\Observers\SocialOriginObserver;
use App\Observers\StructureObserver;
use App\Observers\WorkNormObserver;
use App\Observers\DisabilityObserver;
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
        Structure::observe(StructureObserver::class);
        CountryTranslation::observe(CountryTranslationObserver::class);
        City::observe(CityObserver::class);
        Position::observe(PositionObserver::class);
        Disability::observe(DisabilityObserver::class);
        RankReason::observe(RankReasonObserver::class);
        SocialOrigin::observe(SocialOriginObserver::class);
        EducationDegree::observe(EducationDegreeObserver::class);
        WorkNorm::observe(WorkNormObserver::class);
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
}
