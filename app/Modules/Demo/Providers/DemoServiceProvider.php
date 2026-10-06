<?php

namespace App\Modules\Demo\Providers;

use App\Modules\Demo\Application\Services\DemoDatabaseCloner;
use App\Modules\Demo\Application\Services\DemoTenantRepository;
use App\Modules\Demo\Application\Services\DemoTenantSwitcher;
use App\Modules\Demo\Console\Commands\DemoCreateCommand;
use App\Modules\Demo\Console\Commands\DemoDeleteCommand;
use App\Modules\Demo\Console\Commands\DemoExtendCommand;
use App\Modules\Demo\Console\Commands\DemoListCommand;
use App\Modules\Demo\Http\Middleware\ResolveDemoTenant;
use App\Modules\Demo\Models\DemoTenant;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;

/**
 * Pulsuz demo rejimi. `DEMO_MODE` sönülüdürsə (əsas/prod server) bu provider heç nə
 * etmir — middleware, əmrlər və bağlantılar qeydiyyatdan keçmir.
 */
class DemoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! config('demo.enabled')) {
            return;
        }

        // Müştəri bazası aktiv olanda belə şablon/idarə bazasına çıxış qalsın.
        if (config('database.connections.'.DemoTenant::CONNECTION) === null) {
            config([
                'database.connections.'.DemoTenant::CONNECTION => config('database.connections.'.config('database.default')),
            ]);
        }

        $this->app->singleton(DemoTenantRepository::class);
        $this->app->singleton(DemoTenantSwitcher::class);
        $this->app->singleton(DemoDatabaseCloner::class, fn (): DemoDatabaseCloner => new DemoDatabaseCloner(
            (string) config('database.connections.audit.database'),
        ));

        if ($this->app->runningInConsole()) {
            $this->commands([
                DemoCreateCommand::class,
                DemoListCommand::class,
                DemoExtendCommand::class,
                DemoDeleteCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! config('demo.enabled')) {
            return;
        }

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'demo');
        $this->registerMiddleware();
    }

    /**
     * Middleware `web` qrupunda StartSession-dan dərhal sonra durmalıdır: sessiya artıq
     * oxunub, amma heç bir middleware hələ istifadəçini bazadan yükləməyib.
     */
    private function registerMiddleware(): void
    {
        /** @var HttpKernel $kernel */
        $kernel = $this->app->make(HttpKernelContract::class);
        $groups = $kernel->getMiddlewareGroups();
        $web = array_values(array_diff($groups['web'] ?? [], [ResolveDemoTenant::class]));
        $position = array_search(StartSession::class, $web, true);
        array_splice($web, $position === false ? 0 : $position + 1, 0, [ResolveDemoTenant::class]);
        $groups['web'] = $web;

        $kernel->setMiddlewareGroups($groups);
        $kernel->addToMiddlewarePriorityAfter(StartSession::class, ResolveDemoTenant::class);
    }
}
