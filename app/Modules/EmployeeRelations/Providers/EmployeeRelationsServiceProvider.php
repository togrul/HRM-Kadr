<?php

namespace App\Modules\EmployeeRelations\Providers;

use App\Modules\EmployeeRelations\Application\Services\OrderCancellationTracker;
use App\Modules\EmployeeRelations\Console\Commands\SendSlaRemindersCommand;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Policies\RelationCasePolicy;
use App\Modules\Orders\Contracts\OrderStatusChanged;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * İşçi münasibətləri modulu (şikayət və intizam araşdırması). `config/modules.php`-də
 * `employee-relations` söndürüləndə marşrutlar, menyu bəndi, Şəxsi kabinet bölməsi və
 * xatırlatma cədvəli işləmir.
 */
class EmployeeRelationsServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SendSlaRemindersCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('employee-relations')) {
            return;
        }

        Gate::policy(RelationCase::class, RelationCasePolicy::class);

        // Orders əmrin statusunu dəyişəndə apellyasiya ilə ləğv olunmuş tənbehin əmrini
        // gözləyən iş yenilənir (kontrakt hadisəsi — Orders modelinə asılılıq yoxdur).
        Event::listen(OrderStatusChanged::class, [OrderCancellationTracker::class, 'handleOrderStatusChanged']);

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'employee-relations');

        $this->registerAliases([
            'dashboard' => \App\Modules\EmployeeRelations\Livewire\Dashboard::class,
            'case-detail' => \App\Modules\EmployeeRelations\Livewire\CaseDetail::class,
            'my-cases' => \App\Modules\EmployeeRelations\Livewire\MyCases::class,
        ], 'employee-relations');
    }
}
