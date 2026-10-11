<?php

namespace App\Modules\Benefits\Livewire;

use App\Modules\Benefits\Application\Services\BenefitsAvailability;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Admin → HR siyasəti diaqnostikası: «İmtiyazlar» modulunun şirkət növünə görə standart
 * vəziyyəti və adminin əl ilə seçimi (profil üzrə / açıq / bağlı).
 */
class ModuleSwitch extends Component
{
    public function mount(): void
    {
        abort_unless($this->canManage(), 403);
    }

    public function canManage(): bool
    {
        return Auth::user()?->can('access-admin') ?? false;
    }

    public function setMode(string $mode, BenefitsAvailability $availability): void
    {
        abort_unless($this->canManage(), 403);

        $availability->setOverride(match ($mode) {
            'on' => true,
            'off' => false,
            default => null,
        });

        $this->dispatch('notify', type: 'success', message: __('benefits::dashboard.module.saved'));
    }

    public function render(BenefitsAvailability $availability): View
    {
        $availability->forget();

        return view('benefits::livewire.module-switch', ['state' => $availability->state()]);
    }
}
