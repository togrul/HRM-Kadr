<?php

namespace App\Modules\Benefits\Providers;

use App\Models\Personnel;
use App\Models\Setting;
use App\Modules\Benefits\Application\Services\BenefitLifecycleService;
use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use App\Modules\Benefits\Console\Commands\BenefitsDailyCommand;
use App\Modules\Benefits\Http\Middleware\SyncBenefitsModuleState;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use App\Support\Database\InstalledTables;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * İmtiyazlar (Benefits): maaşdan kənar imtiyaz paketləri, qoşulma, ailə üzvləri, illik
 * yenilənmə, payroll inteqrasiyası, total rewards və xərc hesabatı.
 *
 * Provider modul kataloqunda açıqdırsa yüklənir; şirkət növünə görə və adminin seçimi ilə
 * modulun görünüb-görünməməsi isə hər sorğuda BenefitsAvailability + SyncBenefitsModuleState
 * ilə müəyyən olunur (marşrutlar route cache-ə uyğun olaraq həmişə qeydiyyatdadır).
 */
class BenefitsServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        $this->app->scoped(BenefitsAvailability::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                BenefitsDailyCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('benefits')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'benefits');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'benefits');
        $this->loadMigrations();
        $this->registerAliases($this->componentMap(), 'benefits');
        $this->registerMiddleware();
        $this->listenForTerminations();
        $this->listenForSettingChanges();
    }

    /**
     * Admin seçimi Tənzimləmələr ekranından da dəyişə bilər — keşlənmiş dəyər silinsin.
     */
    private function listenForSettingChanges(): void
    {
        $forget = function (Setting $setting): void {
            if ($setting->getAttribute('name') === BenefitsAvailability::SETTING) {
                BenefitsAvailability::forgetCachedOverride();
            }
        };

        Setting::saved($forget);
        Setting::deleted($forget);
    }

    private function loadMigrations(): void
    {
        $path = $this->app->make(ModuleState::class)->migrationPath('benefits');

        if ($path) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function registerMiddleware(): void
    {
        $this->app->make(HttpKernelContract::class)->appendMiddlewareToGroup('web', SyncBenefitsModuleState::class);
    }

    /**
     * Xitam əmri (və ya kartda əl ilə) işdən çıxma tarixini yazanda/silərkən qoşulmalar izləyir.
     */
    private function listenForTerminations(): void
    {
        Personnel::updated(function (Personnel $personnel): void {
            if (! $personnel->wasChanged('leave_work_date') || ! InstalledTables::has('benefit_enrollments')) {
                return;
            }

            $lifecycle = app(BenefitLifecycleService::class);
            $new = $personnel->getAttributes()['leave_work_date'] ?? null;
            $old = $personnel->getRawOriginal('leave_work_date');

            try {
                if ($new !== null && $new !== '') {
                    $lifecycle->employmentEnded((string) $personnel->tabel_no, CarbonImmutable::parse((string) $new)->toDateString());
                } else {
                    $lifecycle->employmentRestored((string) $personnel->tabel_no, $old !== null ? CarbonImmutable::parse((string) $old)->toDateString() : null);
                }
            } catch (Throwable $exception) {
                // Kadr əməliyyatı (xitam əmri) imtiyaz sinxronuna görə dayanmamalıdır; gündəlik
                // `benefits:daily` əmri vəziyyəti yenidən hesablayır.
                report($exception);
            }
        });
    }

    /**
     * @return array<string, class-string>
     */
    private function componentMap(): array
    {
        return [
            'dashboard' => \App\Modules\Benefits\Livewire\Dashboard::class,
            'tabs.plans' => \App\Modules\Benefits\Livewire\Tabs\PlansTab::class,
            'tabs.enrollments' => \App\Modules\Benefits\Livewire\Tabs\EnrollmentsTab::class,
            'tabs.renewals' => \App\Modules\Benefits\Livewire\Tabs\RenewalsTab::class,
            'tabs.costs' => \App\Modules\Benefits\Livewire\Tabs\CostsTab::class,
            'my-benefits' => \App\Modules\Benefits\Livewire\MyBenefits::class,
            'total-rewards' => \App\Modules\Benefits\Livewire\TotalRewards::class,
            'module-switch' => \App\Modules\Benefits\Livewire\ModuleSwitch::class,
        ];
    }
}
