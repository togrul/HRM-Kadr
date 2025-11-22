<?php

namespace App\Modules\UI\Providers;

use App\Providers\Concerns\RegistersLivewireAliases;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class UIServiceProvider extends ServiceProvider
{
    use RegistersLivewireAliases;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'ui');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $map = [
            'confirmation.add-comment' => \App\Modules\UI\Livewire\Confirmation\AddComment::class,
            'filter.detail' => \App\Modules\UI\Livewire\Filter\Detail::class,
        ];

        $this->registerAliases($map, 'ui');
    }
}
