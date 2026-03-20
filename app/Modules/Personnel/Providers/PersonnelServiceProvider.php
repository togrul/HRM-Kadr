<?php

namespace App\Modules\Personnel\Providers;

use App\Modules\Personnel\Console\Commands\PersonnelListQueryBudgetCommand;
use App\Modules\Personnel\Console\Commands\PersonnelListRenderBenchmarkCommand;
use App\Modules\Personnel\Console\Commands\PersonnelCrudQueryBudgetCommand;
use App\Modules\Personnel\Console\Commands\PersonnelCrudRenderBenchmarkCommand;
use App\Modules\Personnel\Console\Commands\ProfessionalPortfolioBackfillRegistryKeysCommand;
use App\Modules\Personnel\Console\Commands\ProfessionalPortfolioCheckMediaLinksCommand;
use App\Modules\Personnel\Console\Commands\ProfessionalPortfolioEnforcePoliciesCommand;
use App\Modules\Personnel\Console\Commands\ProfessionalPortfolioSyncRegistriesCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PersonnelServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PersonnelListQueryBudgetCommand::class,
                PersonnelListRenderBenchmarkCommand::class,
                PersonnelCrudQueryBudgetCommand::class,
                PersonnelCrudRenderBenchmarkCommand::class,
                ProfessionalPortfolioCheckMediaLinksCommand::class,
                ProfessionalPortfolioBackfillRegistryKeysCommand::class,
                ProfessionalPortfolioSyncRegistriesCommand::class,
                ProfessionalPortfolioEnforcePoliciesCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('personnel')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/print.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'personnel');
        $this->registerPolicies();
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'personnel');
    }

    protected function registerPolicies(): void
    {
        Gate::policy(\App\Models\Personnel::class, \App\Modules\Personnel\Policies\PersonnelPolicy::class);
    }

    protected function componentMap(): array
    {
        return [
            'all-personnel' => \App\Modules\Personnel\Livewire\AllPersonnel::class,
            'table-panel' => \App\Modules\Personnel\Livewire\TablePanel::class,
            'add-personnel' => \App\Modules\Personnel\Livewire\AddPersonnel::class,
            'edit-personnel' => \App\Modules\Personnel\Livewire\EditPersonnel::class,
            'steps.document-step' => \App\Modules\Personnel\Livewire\Steps\DocumentStep::class,
            'steps.education-step' => \App\Modules\Personnel\Livewire\Steps\EducationStep::class,
            'steps.labor-activity-step' => \App\Modules\Personnel\Livewire\Steps\LaborActivityStep::class,
            'steps.history-step' => \App\Modules\Personnel\Livewire\Steps\HistoryStep::class,
            'steps.awards-punishments-step' => \App\Modules\Personnel\Livewire\Steps\AwardsPunishmentsStep::class,
            'steps.kinship-step' => \App\Modules\Personnel\Livewire\Steps\KinshipStep::class,
            'steps.misc-step' => \App\Modules\Personnel\Livewire\Steps\MiscStep::class,
            'delete-personnel' => \App\Modules\Personnel\Livewire\DeletePersonnel::class,
            'files' => \App\Modules\Personnel\Livewire\Files::class,
            'information' => \App\Modules\Personnel\Livewire\Information::class,
            'vacation-list' => \App\Modules\Personnel\Livewire\VacationList::class,
            'professional-portfolio' => \App\Modules\Personnel\Livewire\ProfessionalPortfolio\ProfessionalPortfolio::class,
            'professional-portfolio.events-manager' => \App\Modules\Personnel\Livewire\ProfessionalPortfolio\EventsManager::class,
            'professional-portfolio.media-manager' => \App\Modules\Personnel\Livewire\ProfessionalPortfolio\MediaManager::class,
            'professional-portfolio.projects-manager' => \App\Modules\Personnel\Livewire\ProfessionalPortfolio\ProjectsManager::class,
            'professional-portfolio.timeline-panel' => \App\Modules\Personnel\Livewire\ProfessionalPortfolio\TimelinePanel::class,
            'professional-portfolio.analytics-panel' => \App\Modules\Personnel\Livewire\ProfessionalPortfolio\AnalyticsPanel::class,
        ];
    }

}
