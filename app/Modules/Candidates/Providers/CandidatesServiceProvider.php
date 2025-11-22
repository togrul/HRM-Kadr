<?php

namespace App\Modules\Candidates\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
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
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'candidates');
        $this->registerLivewireComponents();
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
