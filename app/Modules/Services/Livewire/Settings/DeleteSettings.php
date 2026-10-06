<?php

namespace App\Modules\Services\Livewire\Settings;

use App\Models\Setting;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteSettings extends Component
{
    use AuthorizesRequests;
    use AuthorizesSettingsAccess;

    #[Locked]
    public ?int $settingId = null;

    #[On('setDeleteSettings')]
    public function setDeleteSettings($settingId): void
    {
        $setting = Setting::query()
            ->select('id', 'name')
            ->find($settingId);

        if (! $setting) {
            $this->settingId = null;

            return;
        }

        if ($this->refuseInUse($setting)) {
            return;
        }

        $this->settingId = (int) $setting->id;

        $this->dispatch('deleteSettingsWasSet');
    }

    public function deleteSetting(): void
    {
        if (! $this->settingId) {
            return;
        }

        $setting = Setting::query()
            ->select('id', 'name')
            ->find($this->settingId);

        if (! $setting) {
            $this->settingId = null;

            return;
        }

        if ($this->refuseInUse($setting)) {
            $this->settingId = null;

            return;
        }

        $setting->delete();

        $this->settingId = null;

        $this->dispatch('settingsWasDeleted', __('services::settings.messages.deleted'));
    }

    /**
     * Settings the application reads by name (coefficients, chief, candidate presets) cannot be deleted.
     */
    private function refuseInUse(Setting $setting): bool
    {
        $inUse = in_array($setting->name, SettingsList::keysReadByCode(), true);

        if ($inUse) {
            $this->dispatch('notify', type: 'error', message: __('services::settings.messages.in_use'));
        }

        return $inUse;
    }

    public function render(): View
    {
        return view('services::livewire.services.settings.delete-settings');
    }
}
