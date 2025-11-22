<?php

namespace App\Modules\Notifications\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'notification');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('notification.notifications', \App\Modules\Notifications\Livewire\Notifications::class);
        Livewire::component('notification.notification-list', \App\Modules\Notifications\Livewire\NotificationList::class);
    }
}
