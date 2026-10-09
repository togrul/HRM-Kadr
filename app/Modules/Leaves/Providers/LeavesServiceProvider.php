<?php

namespace App\Modules\Leaves\Providers;

use App\Contracts\AbsenceSource;
use App\Models\Leave;
use App\Models\LeaveSickCertificate;
use App\Modules\Leaves\Application\Services\LeaveAbsenceSource;
use App\Modules\Leaves\Application\Services\LeaveListCacheVersion;
use App\Modules\Leaves\Application\Services\OrderAbsenceRecorderService;
use App\Modules\Leaves\Application\Services\SickCertificateAttentionService;
use App\Modules\Leaves\Console\Commands\LeavesQueryBudgetCommand;
use App\Modules\Leaves\Console\Commands\LeavesRenderBenchmarkCommand;
use App\Modules\Leaves\Contracts\OrderAbsenceRecorder;
use App\Modules\Leaves\Contracts\SickCertificateAttention;
use App\Modules\Leaves\Policies\LeaveSickCertificatePolicy;
use App\Observers\LeaveObserver;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class LeavesServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LeavesQueryBudgetCommand::class,
                LeavesRenderBenchmarkCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('leaves')) {
            return;
        }

        // Contributes this module's absences to the cross-module overlap check.
        $this->app->tag([LeaveAbsenceSource::class], AbsenceSource::TAG);
        // Lets an approved order (military muster, donor day…) file its paid absence here.
        $this->app->bind(OrderAbsenceRecorder::class, OrderAbsenceRecorderService::class);
        // Long-open sick certificates for the home page's attention panel.
        $this->app->bind(SickCertificateAttention::class, SickCertificateAttentionService::class);
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'leaves');
        $this->loadMigrations();
        $this->registerObservers();
        $this->registerPolicies();
        $this->registerLivewireComponents();
    }

    protected function loadMigrations(): void
    {
        $path = $this->app->make(ModuleState::class)->migrationPath('leaves');

        if ($path) {
            $this->loadMigrationsFrom($path);
        }
    }

    protected function registerObservers(): void
    {
        Leave::observe(LeaveObserver::class);

        // Any leave write invalidates the list's page/stat caches.
        $bump = function (): void {
            $this->app->make(LeaveListCacheVersion::class)->bump();
        };
        Leave::saved($bump);
        Leave::deleted($bump);
        Leave::restored($bump);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(
            \App\Models\Leave::class,
            \App\Modules\Leaves\Policies\LeavePolicy::class
        );

        Gate::policy(LeaveSickCertificate::class, LeaveSickCertificatePolicy::class);
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'leaves');
    }

    protected function componentMap(): array
    {
        return [
            'leaves' => \App\Modules\Leaves\Livewire\Leaves::class,
            'add-leave' => \App\Modules\Leaves\Livewire\AddLeave::class,
            'edit-leave' => \App\Modules\Leaves\Livewire\EditLeave::class,
            'delete-leave' => \App\Modules\Leaves\Livewire\DeleteLeave::class,
            'sick-certificates' => \App\Modules\Leaves\Livewire\SickCertificates\SickCertificates::class,
            'sick-certificate-editor' => \App\Modules\Leaves\Livewire\SickCertificates\SickCertificateEditor::class,
            'personnel-sick-certificates' => \App\Modules\Leaves\Livewire\SickCertificates\PersonnelSickCertificates::class,
        ];
    }
}
