<?php

namespace App\Modules\Orders\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class OrdersServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('orders')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'orders');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'orders');
    }

    protected function componentMap(): array
    {
        return [
            'all-orders' => \App\Modules\Orders\Livewire\AllOrders::class,
            'add-order' => \App\Modules\Orders\Livewire\AddOrder::class,
            'edit-order' => \App\Modules\Orders\Livewire\EditOrder::class,
            'delete-order' => \App\Modules\Orders\Livewire\DeleteOrder::class,
            'templates.all-templates' => \App\Modules\Orders\Livewire\Templates\AllTemplates::class,
            'templates.add-template' => \App\Modules\Orders\Livewire\Templates\AddTemplate::class,
            'templates.edit-template' => \App\Modules\Orders\Livewire\Templates\EditTemplate::class,
            'templates.delete-template' => \App\Modules\Orders\Livewire\Templates\DeleteTemplate::class,
            'templates.set-type' => \App\Modules\Orders\Livewire\Templates\SetType::class,
        ];
    }
}
