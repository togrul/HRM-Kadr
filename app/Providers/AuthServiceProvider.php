<?php

namespace App\Providers;

use App\Auth\RequestCachedEloquentUserProvider;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Auth::provider('request-cached-eloquent', function ($app, array $config) {
            return new RequestCachedEloquentUserProvider($app['hash'], $config['model']);
        });
    }
}
