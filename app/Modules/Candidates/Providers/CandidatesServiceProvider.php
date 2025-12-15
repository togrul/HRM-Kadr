<?php

namespace App\Modules\Candidates\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use App\Services\Modules\ModuleState;
use Illuminate\Support\ServiceProvider;

class CandidatesServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        //
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
            'delete-candidate' => \App\Modules\Candidates\Livewire\DeleteCandidate::class,
        ];
    }
}
