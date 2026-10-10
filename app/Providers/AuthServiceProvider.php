<?php

namespace App\Providers;

use App\Auth\RequestCachedEloquentUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

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

        $this->configurePasswordRules();
        $this->configureAuthRateLimiters();
    }

    /**
     * Bütün şifrə formalarının (sıfırlama, profil, admin istifadəçi formaları) ortaq qaydası:
     * ən azı 12 simvol, böyük və kiçik hərf, rəqəm; istehsalda sızmış şifrələr bazası ilə yoxlanır.
     */
    private function configurePasswordRules(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(12)->mixedCase()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }

    /**
     * Şifrə bərpası üçün IP səviyyəsində limit: bir ünvandan çoxlu e-poçta link sorğusu
     * (və token yoxlaması) bağlanır. Giriş üçün IP limiti LoginRequest-dədir.
     */
    private function configureAuthRateLimiters(): void
    {
        RateLimiter::for('password-reset-ip', fn (Request $request): Limit => Limit::perMinute(10)->by('password-reset-ip|'.$request->ip()));
    }
}
