<?php

namespace App\Modules\Personnel\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PersonnelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/print.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'personnel');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('personnel.all-personnel', \App\Modules\Personnel\Livewire\AllPersonnel::class);
        Livewire::component('personnel.add-personnel', \App\Modules\Personnel\Livewire\AddPersonnel::class);
        Livewire::component('personnel.edit-personnel', \App\Modules\Personnel\Livewire\EditPersonnel::class);
        Livewire::component('personnel.delete-personnel', \App\Modules\Personnel\Livewire\DeletePersonnel::class);
        Livewire::component('personnel.files', \App\Modules\Personnel\Livewire\Files::class);
        Livewire::component('personnel.information', \App\Modules\Personnel\Livewire\Information::class);
        Livewire::component('personnel.vacation-list', \App\Modules\Personnel\Livewire\VacationList::class);
    }
}
