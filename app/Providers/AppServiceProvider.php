<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Features\FeatureState;
use App\Services\NumberToWordsService;
use App\Services\Profiles\ProfileState;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProfileState::class, fn () => new ProfileState(
            config('profiles.profiles', []),
            (string) config('profiles.active', 'default'),
            config('modules.catalog', []),
        ));

        $this->app->singleton(NumberToWordsService::class, fn () => new NumberToWordsService);
        $this->app->singleton(StructureService::class, fn () => new StructureService);
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
        $this->registerMacros();
        $this->registerBladeDirectives();
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
        Blade::if('module', fn (string $slug) => app(\App\Services\Modules\ModuleState::class)->enabled($slug));
        Blade::if('feature', fn (string $feature) => app(\App\Services\Features\FeatureState::class)->enabled($feature));
    }
}
