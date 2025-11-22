<?php

namespace App\Modules\Services\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['settingsUpdated', 'settingsWasDeleted'])]
class SettingsList extends Component
{
    use AuthorizesRequests;

    public $setting = [];

    public function updatedSetting($value, $name)
    {
        $_key = explode('.', $name)[0];
        $_setting = Setting::where('id', $this->setting[$_key]['id'])->firstOrFail();
        $_setting->update([
            'value' => $value,
        ]);

        $this->dispatch('settingsUpdated', __('Setting was added successfully!'));
    }

    public function setDeleteSettings($settingsId)
    {
        $this->dispatch('setDeleteSettings', $settingsId);
    }

    public function render()
    {
        $settings = Setting::all();

        $this->setting = $settings->toArray();

        return view('services::livewire.services.settings.settings-list', compact('settings'));
    }
}
