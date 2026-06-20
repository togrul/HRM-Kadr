<?php

namespace App\Modules\Notifications\Providers;

use App\Modules\Notifications\Console\Commands\NotificationsQueryBudgetCommand;
use App\Modules\Notifications\Console\Commands\NotificationsRenderBenchmarkCommand;
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
        if ($this->app->runningInConsole()) {
            $this->commands([
                NotificationsQueryBudgetCommand::class,
                NotificationsRenderBenchmarkCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('notifications')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'notification');
        $this->loadMigrations();
        DatabaseNotification::observe(DatabaseNotificationObserver::class);
        $this->registerLivewireComponents();
    }

    protected function loadMigrations(): void
    {
        $path = $this->app->make(ModuleState::class)->migrationPath('notifications');

        if ($path) {
            $this->loadMigrationsFrom($path);
        }
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
            'settings-hub' => \App\Modules\Notifications\Livewire\SettingsHub::class,
            'overview-panel' => \App\Modules\Notifications\Livewire\OverviewPanel::class,
            'analytics-panel' => \App\Modules\Notifications\Livewire\AnalyticsPanel::class,
            'history-board' => \App\Modules\Notifications\Livewire\HistoryBoard::class,
            'template-manager' => \App\Modules\Notifications\Livewire\TemplateManager::class,
            'rule-manager' => \App\Modules\Notifications\Livewire\RuleManager::class,
            'campaign-board' => \App\Modules\Notifications\Livewire\CampaignBoard::class,
            'approval-queue' => \App\Modules\Notifications\Livewire\ApprovalQueue::class,
            'announcement-composer' => \App\Modules\Notifications\Livewire\AnnouncementComposer::class,
        ];
    }
}
