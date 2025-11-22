<?php

namespace App\Modules\Leaves\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use Illuminate\Support\ServiceProvider;

class LeavesServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'leaves');
        $this->registerLivewireComponents();
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
        ];
    }
}
