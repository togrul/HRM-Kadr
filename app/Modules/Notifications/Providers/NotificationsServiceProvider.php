<?php

namespace App\Modules\Notifications\Providers;

use App\Modules\Notifications\Observers\DatabaseNotificationObserver;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\ServiceProvider;

class NotificationsServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('notifications')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'notification');
        DatabaseNotification::observe(DatabaseNotificationObserver::class);
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'notification');
    }

    protected function componentMap(): array
    {
        return [
            'notifications' => \App\Modules\Notifications\Livewire\Notifications::class,
            'notifications-counter' => \App\Modules\Notifications\Livewire\NotificationsCounter::class,
            'notification-list' => \App\Modules\Notifications\Livewire\NotificationList::class,
        ];
    }
}
