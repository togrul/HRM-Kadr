<?php

namespace App\Modules\Staff\Providers;

use Illuminate\Support\ServiceProvider;

class StaffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'staff');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        \Livewire\Livewire::component('staff-schedule.staffs', \App\Modules\Staff\Livewire\Staffs::class);
        \Livewire\Livewire::component('staff-schedule.add-staff', \App\Modules\Staff\Livewire\AddStaff::class);
        \Livewire\Livewire::component('staff-schedule.edit-staff', \App\Modules\Staff\Livewire\EditStaff::class);
        \Livewire\Livewire::component('staff-schedule.delete-staff', \App\Modules\Staff\Livewire\DeleteStaff::class);
        \Livewire\Livewire::component('staff-schedule.show-staff', \App\Modules\Staff\Livewire\ShowStaff::class);
    }
}
