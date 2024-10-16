<?php

namespace App\Livewire\Services\Settings;

use App\Models\Setting;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteSettings extends Component
{
    public ?Setting $setting;

    #[On('setDeleteSettings')]
    public function setDeleteSettings($settingId)
    {
        $this->setting = Setting::findOrFail($settingId);

        $this->dispatch('deleteSettingsWasSet');
    }

    public function deleteSetting()
    {
        // $this->authorize('delete',$this->comment);

        Setting::destroy($this->setting->id);

        $this->setting = null;

        $this->dispatch('settingsWasDeleted', __('Setting was deleted!'));
    }

    public function render()
    {
        return view('livewire.services.settings.delete-settings');
    }
}
