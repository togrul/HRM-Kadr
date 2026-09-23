<?php

namespace App\Modules\Admin\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        return view('admin::livewire.admin.dashboard');
    }
}
