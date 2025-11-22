<?php

namespace App\Modules\Candidates\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CandidatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'candidates');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('candidates.candidate-list', \App\Modules\Candidates\Livewire\CandidateList::class);
        Livewire::component('candidates.add-candidate', \App\Modules\Candidates\Livewire\AddCandidate::class);
        Livewire::component('candidates.edit-candidate', \App\Modules\Candidates\Livewire\EditCandidate::class);
        Livewire::component('candidates.delete-candidate', \App\Modules\Candidates\Livewire\DeleteCandidate::class);
    }
}
