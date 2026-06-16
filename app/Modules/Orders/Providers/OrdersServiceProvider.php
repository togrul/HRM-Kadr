<?php

namespace App\Modules\Orders\Providers;

use App\Models\OrderType;
use App\Modules\Orders\Domain\Contracts\AccessibleStructureScopeReadRepository;
use App\Modules\Orders\Domain\Contracts\OrderTemplateAdmin;
use App\Modules\Orders\Domain\Contracts\OrderTemplateReadRepository;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRegistry;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRepository;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\PersonnelLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\RankPositionLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\StructureLookupReadRepository;
use App\Modules\Orders\Console\Commands\OrdersListQueryBudgetCommand;
use App\Modules\Orders\Console\Commands\OrdersListRenderBenchmarkCommand;
use App\Modules\Orders\Infrastructure\Persistence\Eloquent\EloquentOrderTypeStatusLookupReadRepository;
use App\Modules\Orders\Infrastructure\Persistence\Eloquent\EloquentOrderTemplateReadRepository;
use App\Modules\Orders\Infrastructure\Persistence\Eloquent\EloquentOrderTemplateRepository;
use App\Modules\Orders\Infrastructure\Persistence\Eloquent\EloquentPersonnelLookupReadRepository;
use App\Modules\Orders\Infrastructure\Persistence\Eloquent\EloquentRankPositionLookupReadRepository;
use App\Modules\Orders\Infrastructure\Persistence\Eloquent\EloquentStructureLookupReadRepository;
use App\Modules\Orders\Infrastructure\Persistence\Eloquent\StructureServiceAccessibleStructureScopeReadRepository;
use App\Observers\OrderTypeObserver;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Orders\TemplateAdminService;
use App\Services\Orders\TemplateRegistry as TemplateRegistryService;
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
                \App\Modules\Orders\Console\Commands\SeedOrderBlockTemplatesCommand::class,
            ]);
        }

        $this->app->bind(OrderTemplateRepository::class, EloquentOrderTemplateRepository::class);
        $this->app->bind(OrderTemplateReadRepository::class, EloquentOrderTemplateReadRepository::class);
        $this->app->bind(OrderTemplateAdmin::class, TemplateAdminService::class);
        $this->app->bind(OrderTemplateRegistry::class, TemplateRegistryService::class);
        $this->app->bind(AccessibleStructureScopeReadRepository::class, StructureServiceAccessibleStructureScopeReadRepository::class);
        $this->app->bind(OrderTypeStatusLookupReadRepository::class, EloquentOrderTypeStatusLookupReadRepository::class);
        $this->app->bind(PersonnelLookupReadRepository::class, EloquentPersonnelLookupReadRepository::class);
        $this->app->bind(StructureLookupReadRepository::class, EloquentStructureLookupReadRepository::class);
        $this->app->bind(RankPositionLookupReadRepository::class, EloquentRankPositionLookupReadRepository::class);
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
            'add-order' => \App\Modules\Orders\Livewire\AddOrder::class,
            'edit-order' => \App\Modules\Orders\Livewire\EditOrder::class,
            'delete-order' => \App\Modules\Orders\Livewire\DeleteOrder::class,
            'templates.all-templates' => \App\Modules\Orders\Livewire\Templates\AllTemplates::class,
            'templates.add-template' => \App\Modules\Orders\Livewire\Templates\AddTemplate::class,
            'templates.edit-template' => \App\Modules\Orders\Livewire\Templates\EditTemplate::class,
            'templates.delete-template' => \App\Modules\Orders\Livewire\Templates\DeleteTemplate::class,
            'templates.set-type' => \App\Modules\Orders\Livewire\Templates\SetType::class,
            'templates.onboarding-wizard' => \App\Modules\Orders\Livewire\Templates\OnboardingWizard::class,
        ];
    }

    protected function registerObservers(): void
    {
        OrderType::observe(OrderTypeObserver::class);
    }
}
