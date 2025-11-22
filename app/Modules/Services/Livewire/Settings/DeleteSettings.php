<?php

namespace App\Modules\Services\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteSettings extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $settingId = null;

    #[On('setDeleteSettings')]
    public function setDeleteSettings($settingId)
    {
        $setting = Setting::query()
            ->select('id')
            ->find($settingId);

        if (! $setting) {
            $this->settingId = null;

            return;
        }

        // $this->authorize('delete', $setting);

        $this->settingId = (int) $setting->id;

        $this->dispatch('deleteSettingsWasSet');
    }

    public function deleteSetting()
    {
        if (! $this->settingId) {
            return;
        }

        $setting = Setting::query()
            ->select('id')
            ->find($this->settingId);

        if (! $setting) {
            $this->settingId = null;

            return;
        }

        // $this->authorize('delete', $setting);

        $setting->delete();

        $this->settingId = null;

        $this->dispatch('settingsWasDeleted', __('Setting was deleted!'));
    }

    public function render()
    {
        return view('services::livewire.services.settings.delete-settings');
    }
}
