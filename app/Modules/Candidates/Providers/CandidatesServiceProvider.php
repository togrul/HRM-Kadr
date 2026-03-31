<?php

namespace App\Modules\Candidates\Providers;

use App\Modules\Candidates\Console\Commands\CandidateListQueryBudgetCommand;
use App\Modules\Candidates\Console\Commands\CandidateListRenderBenchmarkCommand;
use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class CandidatesServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CandidateListQueryBudgetCommand::class,
                CandidateListRenderBenchmarkCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        if (! $this->app->make(ModuleState::class)->enabled('candidates')) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'candidates');
        $this->registerPolicies();
        $this->registerLivewireComponents();
    }

    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\Candidate::class,
            \App\Modules\Candidates\Policies\CandidatePolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\CandidateApplication::class,
            \App\Modules\Candidates\Policies\CandidateApplicationPolicy::class
        );
    }

    protected function registerLivewireComponents(): void
    {
        $this->registerAliases($this->componentMap(), 'candidates');
    }

    protected function componentMap(): array
    {
        return [
            'candidate-list' => \App\Modules\Candidates\Livewire\CandidateList::class,
            'add-candidate' => \App\Modules\Candidates\Livewire\AddCandidate::class,
            'edit-candidate' => \App\Modules\Candidates\Livewire\EditCandidate::class,
            'candidate-files' => \App\Modules\Candidates\Livewire\CandidateFiles::class,
            'delete-candidate' => \App\Modules\Candidates\Livewire\DeleteCandidate::class,
            'requisition-list' => \App\Modules\Candidates\Livewire\RequisitionList::class,
            'requisition-detail' => \App\Modules\Candidates\Livewire\RequisitionDetail::class,
            'add-requisition' => \App\Modules\Candidates\Livewire\AddRequisition::class,
            'edit-requisition' => \App\Modules\Candidates\Livewire\EditRequisition::class,
            'opening-list' => \App\Modules\Candidates\Livewire\OpeningList::class,
            'opening-detail' => \App\Modules\Candidates\Livewire\OpeningDetail::class,
            'add-opening' => \App\Modules\Candidates\Livewire\AddOpening::class,
            'edit-opening' => \App\Modules\Candidates\Livewire\EditOpening::class,
            'add-application' => \App\Modules\Candidates\Livewire\AddApplication::class,
            'application-pipeline' => \App\Modules\Candidates\Livewire\ApplicationPipeline::class,
            'application-detail' => \App\Modules\Candidates\Livewire\ApplicationDetail::class,
            'application-stage-action-panel' => \App\Modules\Candidates\Livewire\ApplicationStageActionPanel::class,
            'application-stage-timeline-panel' => \App\Modules\Candidates\Livewire\ApplicationStageTimelinePanel::class,
            'application-artifact-timeline-panel' => \App\Modules\Candidates\Livewire\ApplicationArtifactTimelinePanel::class,
            'recruitment-analytics' => \App\Modules\Candidates\Livewire\RecruitmentAnalytics::class,
        ];
    }
}
