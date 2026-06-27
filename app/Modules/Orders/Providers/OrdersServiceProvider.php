<?php

namespace App\Modules\Orders\Providers;

use App\Models\OrderType;
use App\Modules\Orders\Console\Commands\OrdersListQueryBudgetCommand;
use App\Modules\Orders\Console\Commands\OrdersListRenderBenchmarkCommand;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use App\Modules\Orders\Infrastructure\Persistence\Eloquent\EloquentOrderTypeStatusLookupReadRepository;
use App\Observers\OrderTypeObserver;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class OrdersServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                OrdersListQueryBudgetCommand::class,
                OrdersListRenderBenchmarkCommand::class,
            ]);
        }

        $this->app->bind(OrderTypeStatusLookupReadRepository::class, EloquentOrderTypeStatusLookupReadRepository::class);
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('orders')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'orders');
        $this->registerObservers();
        $this->registerPolicies();
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'orders');
    }

    protected function registerPolicies(): void
    {
        Gate::policy(\App\Models\Order::class, \App\Modules\Orders\Policies\OrderPolicy::class);
        Gate::policy(\App\Models\OrderLog::class, \App\Modules\Orders\Policies\OrderLogPolicy::class);
    }

    protected function componentMap(): array
    {
        return [
            'all-orders' => \App\Modules\Orders\Livewire\AllOrders::class,
            'order-composer' => \App\Modules\Orders\Livewire\OrderComposer::class,
            'template-designer' => \App\Modules\Orders\Livewire\OrderTemplateDesigner::class,
            'delete-order' => \App\Modules\Orders\Livewire\DeleteOrder::class,
        ];
    }

    protected function registerObservers(): void
    {
        OrderType::observe(OrderTypeObserver::class);
    }
}
