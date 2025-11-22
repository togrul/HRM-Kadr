<?php

namespace App\Modules\Staff\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use Illuminate\Support\ServiceProvider;

class StaffServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

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
        $this->registerAliases($this->componentMap(), 'staff-schedule');
    }

    protected function componentMap(): array
    {
        return [
            'staffs' => \App\Modules\Staff\Livewire\Staffs::class,
            'add-staff' => \App\Modules\Staff\Livewire\AddStaff::class,
            'edit-staff' => \App\Modules\Staff\Livewire\EditStaff::class,
            'delete-staff' => \App\Modules\Staff\Livewire\DeleteStaff::class,
            'show-staff' => \App\Modules\Staff\Livewire\ShowStaff::class,
        ];
    }
}
