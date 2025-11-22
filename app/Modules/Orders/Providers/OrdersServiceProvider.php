<?php

namespace App\Modules\Orders\Providers;

use Illuminate\Support\ServiceProvider;

class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'orders');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        \Livewire\Livewire::component('orders.all-orders', \App\Modules\Orders\Livewire\AllOrders::class);
        \Livewire\Livewire::component('orders.add-order', \App\Modules\Orders\Livewire\AddOrder::class);
        \Livewire\Livewire::component('orders.edit-order', \App\Modules\Orders\Livewire\EditOrder::class);
        \Livewire\Livewire::component('orders.delete-order', \App\Modules\Orders\Livewire\DeleteOrder::class);
        \Livewire\Livewire::component('orders.templates.all-templates', \App\Modules\Orders\Livewire\Templates\AllTemplates::class);
        \Livewire\Livewire::component('orders.templates.add-template', \App\Modules\Orders\Livewire\Templates\AddTemplate::class);
        \Livewire\Livewire::component('orders.templates.edit-template', \App\Modules\Orders\Livewire\Templates\EditTemplate::class);
        \Livewire\Livewire::component('orders.templates.delete-template', \App\Modules\Orders\Livewire\Templates\DeleteTemplate::class);
        \Livewire\Livewire::component('orders.templates.set-type', \App\Modules\Orders\Livewire\Templates\SetType::class);
    }
}
