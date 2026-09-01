<?php

namespace App\Modules\Integration\Providers;

use App\Modules\Integration\Console\Commands\IssueIntegrationTokenCommand;
use App\Modules\Integration\Console\Commands\PullFromFinanceCommand;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;
use App\Modules\Integration\Infrastructure\EloquentIntegrationOutbox;
use App\Services\Modules\ModuleState;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * The integration module: an API surface, nothing else.
 *
 * No Livewire components, no views, no menu entry — it exists so another system
 * can read what this one owns. Turning the module off removes the routes
 * entirely, which is the intended way to run a standalone installation.
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([IssueIntegrationTokenCommand::class, PullFromFinanceCommand::class]);
        }

        // This provider is only loaded when the module is enabled, so reaching
        // here already means someone is reading the feed. The no-op default
        // lives in AppServiceProvider, which is always loaded — without it the
        // Orders engine could not resolve its dependency in a standalone
        // installation and would stop working entirely.
        $this->app->bind(IntegrationOutbox::class, EloquentIntegrationOutbox::class);
    }

    public function boot(): void
    {
        $this->registerRateLimiter();

        if (! $this->app->make(ModuleState::class)->enabled('integration')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'integration');
    }

    /**
     * Back-pressure for the integration API.
     *
     * The key comes from the PRESENTED token, not the resolved one: the limiter
     * runs before authentication, and it is exactly the failed attempts that
     * need limiting. The raw value is hashed so a secret never lands in a cache
     * key.
     */
    private function registerRateLimiter(): void
    {
        RateLimiter::for('integration', function (Request $request): Limit {
            $bearer = $request->bearerToken();

            $key = $bearer
                ? 'integration-token:'.substr(hash('sha256', 'ratelimit|'.$bearer), 0, 32)
                : 'integration-ip:'.$request->ip();

            return Limit::perMinute(120)->by($key);
        });
    }
}
